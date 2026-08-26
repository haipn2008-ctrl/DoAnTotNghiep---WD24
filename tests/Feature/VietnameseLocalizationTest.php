<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class VietnameseLocalizationTest extends TestCase
{
    public function test_validation_messages_are_displayed_in_vietnamese(): void
    {
        $validator = Validator::make(['status' => 'active'], [
            'password' => ['required'],
            'status' => ['prohibited'],
        ]);

        $this->assertSame('Vui lòng nhập mật khẩu.', $validator->errors()->first('password'));
        $this->assertSame('Không được phép nhập trạng thái.', $validator->errors()->first('status'));
    }

    public function test_http_error_page_is_displayed_in_vietnamese(): void
    {
        $this->withoutVite();

        $this->get('/duong-dan-khong-ton-tai')
            ->assertNotFound()
            ->assertSee('Không tìm thấy nội dung')
            ->assertSee('Nội dung bạn yêu cầu không tồn tại hoặc đã được di chuyển.');
    }

    public function test_flash_messages_are_only_rendered_by_shared_layouts(): void
    {
        $allowedViews = [
            'auth/activate.blade.php',
            'layouts/admin/index.blade.php',
            'layouts/client/index.blade.php',
        ];

        $viewsRenderingFlashMessages = collect(File::allFiles(resource_path('views')))
            ->filter(fn ($file) => str_contains($file->getContents(), "session('success')")
                || str_contains($file->getContents(), "session('error')"))
            ->map(fn ($file) => str_replace('\\', '/', $file->getRelativePathname()))
            ->sort()
            ->values()
            ->all();

        sort($allowedViews);

        $this->assertSame($allowedViews, $viewsRenderingFlashMessages);
    }
}
