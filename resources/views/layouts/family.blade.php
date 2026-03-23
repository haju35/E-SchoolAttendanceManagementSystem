@extends('layouts.app')

@section('title', 'Family Panel - ' . ($title ?? 'Dashboard'))

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-2 d-md-block bg-light sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('family.dashboard') ? 'active' : '' }}" 
                           href="{{ route('family.dashboard') }}">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('family.children*') ? 'active' : '' }}" 
                           href="{{ route('family.children.index') }}">
                            <i class="fas fa-child"></i> My Children
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('family.attendance*') ? 'active' : '' }}" 
                           href="{{ route('family.attendance.index') }}">
                            <i class="fas fa-calendar-check"></i> Attendance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('family.notifications*') ? 'active' : '' }}" 
                           href="{{ route('family.notifications.index') }}">
                            <i class="fas fa-bell"></i> Notifications
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('family.profile*') ? 'active' : '' }}" 
                           href="{{ route('family.profile.show') }}">
                            <i class="fas fa-user"></i> Profile
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">@yield('page-title')</h1>
                @yield('page-actions')
            </div>
            
            @yield('family-content')
        </main>
    </div>
</div>
@endsection

@push('styles')
<style>
    .sidebar {
        position: fixed;
        top: 56px;
        bottom: 0;
        left: 0;
        z-index: 100;
        padding: 48px 0 0;
        box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
    }
    
    .sidebar .nav-link {
        font-weight: 500;
        color: #333;
    }
    
    .sidebar .nav-link.active {
        color: #0d6efd;
        background-color: #e7f1ff;
    }
    
    .sidebar .nav-link i {
        margin-right: 10px;
    }
    
    main {
        margin-left: 16.666%;
    }
    
    @media (max-width: 768px) {
        .sidebar {
            position: static;
            padding-top: 0;
        }
        main {
            margin-left: 0;
        }
    }
</style>
@endpush