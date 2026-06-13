<html>
    <head>
      <title>@yield('title')</title>
      @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        @include('components.header')
        @section('sidebar')
            This is the master sidebar.
        @show
 
        <div class="container">
            @yield('content')
        </div>
        @include('components.header')
    </body>
</html>