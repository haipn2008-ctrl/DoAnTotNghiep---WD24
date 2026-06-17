@extends('layouts.admin.home')

@section('content')

<h2>Danh sách khách thuê</h2>

<a href="{{ route('admin.tenants.create') }}"
   class="btn btn-primary">
    Thêm khách thuê
</a>

<br><br>

<table border="1" width="100%" cellpadding="10">

    <thead>
        <tr>
            <th>ID</th>
            <th>Họ tên</th>
            <th>CCCD</th>
            <th>SĐT</th>
            <th>Email</th>
            <th>Địa chỉ</th>
            <th>Thao tác</th>
        </tr>
    </thead>

    <tbody>

        @forelse($tenants as $tenant)

        <tr>

            <td>{{ $tenant->id }}</td>

            <td>{{ $tenant->full_name }}</td>

            <td>{{ $tenant->cccd }}</td>

            <td>{{ $tenant->phone }}</td>

            <td>{{ $tenant->email }}</td>

            <td>{{ $tenant->address }}</td>

            <td>

                <a href="{{ route('admin.tenants.edit', $tenant->id) }}">
                    Sửa
                </a>

                |

                <form
                    action="{{ route('admin.tenants.destroy', $tenant->id) }}"
                    method="POST"
                    style="display:inline"
                >

                    @csrf
                    @method('DELETE')

                    <button
                        type="submit"
                        onclick="return confirm('Bạn có chắc muốn xóa?')"
                    >
                        Xóa
                    </button>

                </form>

            </td>

        </tr>

        @empty

        <tr>
            <td colspan="7">
                Chưa có khách thuê nào
            </td>
        </tr>

        @endforelse

    </tbody>

</table>

<br>

{{ $tenants->links() }}

@endsection
