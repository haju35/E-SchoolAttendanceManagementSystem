@extends('layouts.app')

@section('title', 'Admin Panel - ' . ($title ?? 'Dashboard'))

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-2 d-md-block bg-light sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" 
                           href="{{ route('admin.dashboard') }}">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.students*') ? 'active' : '' }}" 
                           href="{{ route('admin.students.index') }}">
                            <i class="fas fa-user-graduate"></i> Students
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.teachers*') ? 'active' : '' }}" 
                           href="{{ route('admin.teachers.index') }}">
                            <i class="fas fa-chalkboard-user"></i> Teachers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.families*') ? 'active' : '' }}" 
                           href="{{ route('admin.families.index') }}">
                            <i class="fas fa-users"></i> Families
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.classes*') ? 'active' : '' }}" 
                           href="{{ route('admin.classes.index') }}">
                            <i class="fas fa-building"></i> Classes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.sections*') ? 'active' : '' }}" 
                           href="{{ route('admin.sections.index') }}">
                            <i class="fas fa-layer-group"></i> Sections
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.subjects*') ? 'active' : '' }}" 
                           href="{{ route('admin.subjects.index') }}">
                            <i class="fas fa-book"></i> Subjects
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.academic-years*') ? 'active' : '' }}" 
                           href="{{ route('admin.academic-years.index') }}">
                            <i class="fas fa-calendar-alt"></i> Academic Years
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.terms*') ? 'active' : '' }}" 
                           href="{{ route('admin.terms.index') }}">
                            <i class="fas fa-calendar-week"></i> Terms
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.assignments*') ? 'active' : '' }}" 
                           href="{{ route('admin.assignments.index') }}">
                            <i class="fas fa-user-tie"></i> Teacher Assignments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.reports*') ? 'active' : '' }}" 
                           href="{{ route('admin.reports.attendance') }}">
                            <i class="fas fa-chart-line"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.config*') ? 'active' : '' }}" 
                           href="{{ route('admin.config') }}">
                            <i class="fas fa-cog"></i> Settings
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
            
            @yield('admin-content')
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