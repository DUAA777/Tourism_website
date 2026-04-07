@extends('layout.admin')

@section('title', 'Dashboard')

@section('content')
<div class="dashboard">
    <h1>Dashboard</h1>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="ri-user-line"></i></div>
            <div class="stat-info">
                <h3>Total Users</h3>
                <p>{{ $total_users }}</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="ri-shield-user-line"></i></div>
            <div class="stat-info">
                <h3>Admins</h3>
                <p>{{ $total_admins }}</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="ri-restaurant-line"></i></div>
            <div class="stat-info">
                <h3>Restaurants</h3>
                <p>{{ $total_restaurants }}</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="ri-hotel-line"></i></div>
            <div class="stat-info">
                <h3>Hotels</h3>
                <p>{{ $total_hotels }}</p>
            </div>
        </div>
    </div>
    
    <div class="recent-sections">
        <div class="recent-card">
            <h3>Recent Users</h3>
            <table class="data-table">
                <thead>
                    <tr><th>Name</th><th>Email</th><th>Role</th></tr>
                </thead>
                <tbody>
                    @foreach($recent_users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->is_admin ? 'Admin' : 'User' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="recent-card">
            <h3>Recent Restaurants</h3>
            <table class="data-table">
                <thead>
                    <tr><th>Name</th><th>Location</th><th>Rating</th></tr>
                </thead>
                <tbody>
                    @foreach($recent_restaurants as $restaurant)
                    <tr>
                        <td>{{ $restaurant->restaurant_name }}</td>
                        <td>{{ $restaurant->location }}</td>
                        <td>{{ $restaurant->rating ?? 'N/A' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="recent-card">
            <h3>Recent Hotels</h3>
            <table class="data-table">
                <thead>
                    <tr><th>Name</th><th>Address</th><th>Rating</th></tr>
                </thead>
                <tbody>
                    @foreach($recent_hotels as $hotel)
                    <tr>
                        <td>{{ $hotel->hotel_name }}</td>
                        <td>{{ $hotel->address ?? 'N/A' }}</td>
                        <td>{{ $hotel->rating_score ?? 'N/A' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection