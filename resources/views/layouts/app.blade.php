<html>
    <head>
      <title>@yield('title')</title>
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