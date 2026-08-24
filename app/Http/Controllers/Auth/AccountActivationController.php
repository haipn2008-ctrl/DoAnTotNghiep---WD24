<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\AdultDateOfBirth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountActivationController extends Controller
{
    private const STEPS = ['personal', 'identity', 'contact', 'password'];

    public function show(Request $request, string $step = 'personal'): View|RedirectResponse
    {
        $account = User::query()->with(['role', 'tenant'])->findOrFail($request->user()->id);
        if ($account->isActive()) {
            return redirect()->route('dashboard');
        }

        abort_unless($account->isClient() && $account->status === User::STATUS_PENDING, 403);
        abort_unless(in_array($step, self::STEPS, true), 404);

        $progress = $this->progress($request, $account);
        if ($missingStep = $this->firstIncompleteStepBefore($step, $progress['completed'])) {
            return redirect()->route('account.activation.step.show', $missingStep);
        }

        return view('auth.activate', [
            'user' => $account,
            'step' => $step,
            'stepNumber' => array_search($step, self::STEPS, true) + 1,
            'stepCount' => count(self::STEPS),
            'profile' => array_merge($this->profileDefaults($account), $progress['data']),
        ]);
    }

    public function store(Request $request, string $step): RedirectResponse
    {
        $account = $this->pendingClient($request);
        abort_unless(in_array($step, self::STEPS, true), 404);

        $progress = $this->progress($request, $account);
        if ($missingStep = $this->firstIncompleteStepBefore($step, $progress['completed'])) {
            return redirect()->route('account.activation.step.show', $missingStep);
        }

        if ($step === 'password') {
            return $this->complete($request, $account, $progress);
        }

        $data = $request->validate($this->stepRules($step, $account, $progress['data']));
        $progress['data'] = array_merge($progress['data'], $data);
        $progress['completed'][$step] = true;
        $request->session()->put($this->sessionKey($account), $progress);

        $nextStep = self::STEPS[array_search($step, self::STEPS, true) + 1];

        return redirect()->route('account.activation.step.show', $nextStep);
    }

    private function complete(Request $request, User $account, array $progress): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        if (Hash::check($data['password'], $account->password)) {
            throw ValidationException::withMessages([
                'password' => 'Mật khẩu mới phải khác mật khẩu tạm thời.',
            ]);
        }

        $profile = array_merge($this->profileDefaults($account), $progress['data']);
        $validator = Validator::make($profile, $this->completeProfileRules($account));
        if ($validator->fails()) {
            $request->session()->put($this->sessionKey($account), ['data' => $progress['data'], 'completed' => []]);

            return redirect()->route('account.activation.step.show', 'personal')
                ->withErrors($validator)
                ->with('error', 'Thông tin kích hoạt chưa đầy đủ. Vui lòng kiểm tra lại từ bước đầu.');
        }

        $profile = $validator->validated();
        DB::transaction(function () use ($request, $profile, $data): void {
            $user = User::with('tenant')->lockForUpdate()->findOrFail($request->user()->id);
            abort_unless($user->isClient() && $user->status === User::STATUS_PENDING, 403);

            $user->update([
                'name' => $profile['name'],
                'phone' => $profile['phone'],
                'password' => $data['password'],
                'status' => User::STATUS_ACTIVE,
                'activated_at' => now(),
                'terms_accepted_at' => now(),
                'must_change_password' => false,
            ]);

            $user->tenant()->updateOrCreate([], [
                'full_name' => $profile['name'],
                'date_of_birth' => $profile['date_of_birth'],
                'gender' => $profile['gender'],
                'cccd' => $profile['cccd'],
                'cccd_issue_date' => $profile['cccd_issue_date'],
                'cccd_issue_place' => $profile['cccd_issue_place'],
                'phone' => $profile['phone'],
                'email' => $user->email,
                'address' => $profile['address'],
            ]);
        });

        $request->session()->forget($this->sessionKey($account));
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Kích hoạt tài khoản thành công. Hồ sơ khách thuê đã được tạo.');
    }

    private function pendingClient(Request $request): User
    {
        $account = User::query()->with(['role', 'tenant'])->findOrFail($request->user()->id);
        abort_unless($account->isClient() && $account->status === User::STATUS_PENDING, 403);

        return $account;
    }

    private function stepRules(string $step, User $account, array $stored): array
    {
        $tenantId = $account->tenant?->id;

        return match ($step) {
            'personal' => [
                'name' => ['required', 'string', 'max:255'],
                'date_of_birth' => ['required', 'date', new AdultDateOfBirth],
                'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            ],
            'identity' => [
                'cccd' => ['required', 'digits:12', Rule::unique('tenants', 'cccd')->ignore($tenantId)],
                'cccd_issue_date' => ['required', 'date', 'before_or_equal:today', 'after:'.($stored['date_of_birth'] ?? '1900-01-01')],
                'cccd_issue_place' => ['required', 'string', 'max:255'],
            ],
            'contact' => [
                'phone' => [
                    'required', 'regex:/^[0-9]{10,15}$/',
                    Rule::unique('users', 'phone')->ignore($account->id),
                    Rule::unique('tenants', 'phone')->ignore($tenantId),
                ],
                'address' => ['required', 'string', 'max:500'],
                'accept_terms' => ['accepted'],
            ],
            default => [],
        };
    }

    private function completeProfileRules(User $account): array
    {
        $tenantId = $account->tenant?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date', new AdultDateOfBirth],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'cccd' => ['required', 'digits:12', Rule::unique('tenants', 'cccd')->ignore($tenantId)],
            'cccd_issue_date' => ['required', 'date', 'before_or_equal:today', 'after:date_of_birth'],
            'cccd_issue_place' => ['required', 'string', 'max:255'],
            'phone' => [
                'required', 'regex:/^[0-9]{10,15}$/',
                Rule::unique('users', 'phone')->ignore($account->id),
                Rule::unique('tenants', 'phone')->ignore($tenantId),
            ],
            'address' => ['required', 'string', 'max:500'],
            'accept_terms' => ['accepted'],
        ];
    }

    private function progress(Request $request, User $account): array
    {
        return array_merge(['data' => [], 'completed' => []], $request->session()->get($this->sessionKey($account), []));
    }

    private function profileDefaults(User $account): array
    {
        return [
            'name' => $account->tenant?->full_name ?: $account->name,
            'date_of_birth' => $account->tenant?->date_of_birth?->format('Y-m-d'),
            'gender' => $account->tenant?->gender,
            'cccd' => $account->tenant?->cccd,
            'cccd_issue_date' => $account->tenant?->cccd_issue_date?->format('Y-m-d'),
            'cccd_issue_place' => $account->tenant?->cccd_issue_place,
            'phone' => $account->phone ?: $account->tenant?->phone,
            'address' => $account->tenant?->address,
            'accept_terms' => null,
        ];
    }

    private function firstIncompleteStepBefore(string $step, array $completed): ?string
    {
        $stepIndex = array_search($step, self::STEPS, true);
        foreach (array_slice(self::STEPS, 0, $stepIndex) as $previousStep) {
            if (! ($completed[$previousStep] ?? false)) {
                return $previousStep;
            }
        }

        return null;
    }

    private function sessionKey(User $account): string
    {
        return "account_activation.{$account->id}";
    }
}
