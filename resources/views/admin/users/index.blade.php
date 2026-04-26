@extends('layout.admin')

@section('title', 'Manage Users')

@section('content')
<div class="management-section">
    <div class="section-header">
        <h1>Users Management</h1>
        <a href="{{ route('admin.users.create') }}" class="btn-primary">
            <i class="ri-add-line"></i> Add New User
        </a>
    </div>
    
    <form method="GET" class="search-form user-filter-form">
        <div class="filter-control filter-control-search">
            <i class="ri-search-line"></i>
            <input type="text" name="search" placeholder="Search by name or email..." value="{{ request('search') }}">
        </div>
        <div class="filter-control filter-control-select">
            <select name="role" onchange="this.form.submit()">
                <option value="">All Roles</option>
                <option value="user" {{ request('role') == 'user' ? 'selected' : '' }}>User</option>
                <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
            </select>
        </div>
    </form>
    
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->phone ?? 'N/A' }}</td>
                    <td>
                        <span class="badge {{ $user->is_admin ? 'badge-admin' : 'badge-user' }}">
                            {{ $user->is_admin ? 'Admin' : 'User' }}
                        </span>
                    </td>
                    <td>{{ $user->created_at->format('Y-m-d') }}</td>
                    <td class="actions">
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn-icon btn-edit">
                            <i class="ri-edit-line"></i>
                        </a>
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline-form js-delete-form"
                              data-delete-title="Delete user?"
                              data-delete-message="Delete {{ $user->name }}? This removes the account and cannot be undone."
                              data-delete-confirm="Delete User">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-icon btn-delete" title="Delete">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    {{ $users->appends(request()->query())->links() }}
</div>
@endsection
