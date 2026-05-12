<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', '876 ASIAW ERP') }} - Login</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900 bg-gray-50">
        
        <!-- Mobile Wrapper -->
        <div class="relative min-h-screen max-w-md mx-auto bg-white shadow-2xl overflow-hidden flex flex-col justify-center">
            
            <!-- Background Decoration -->
            <div class="absolute top-0 left-0 right-0 h-72 bg-gradient-to-br from-indigo-600 to-blue-700 rounded-b-[3rem] z-0 shadow-lg"></div>
            
            <div class="relative z-10 px-6 w-full">
                <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8">
                    <!-- Header -->
                    <div class="flex flex-col items-center mb-8">
                        <div class="w-16 h-16 bg-gradient-to-tr from-indigo-100 to-indigo-50 rounded-2xl flex items-center justify-center shadow-inner mb-4 rotate-3">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-indigo-600 -rotate-3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.999 2.999 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.999 2.999 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" />
                            </svg>
                        </div>
                        <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Masuk Akun</h2>
                        <p class="text-sm text-gray-500 mt-1">Sistem ERP 876 ASIAW</p>
                    </div>

                    {{ $slot }}
                </div>
            </div>

            <!-- Footer Text -->
            <div class="absolute bottom-6 left-0 right-0 text-center text-xs text-gray-400 font-medium">
                &copy; {{ date('Y') }} 876 ASIAW. All rights reserved.
            </div>
        </div>
    </body>
</html>
