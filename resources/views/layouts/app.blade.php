<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    {{-- font awesome --}}
    <link rel="stylesheet" data-purpose="Layout StyleSheet" title="Web Awesome"
        href="/css/app-wa-462d1fe84b879d730fe2180b0e0354e0.css?vsn=d">
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.5.1/css/all.css">
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.5.1/css/sharp-thin.css">
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.5.1/css/sharp-solid.css">
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.5.1/css/sharp-regular.css">
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.5.1/css/sharp-light.css">

    {{-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/animate.css@4/animate.min.css"> --}}
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.6/dist/sweetalert2.min.css" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/loader.css') }}">
    <style>
        /* Hide the default scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        /* Track */
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        /* Handle */
        ::-webkit-scrollbar-thumb {
            background: #3be975;
        }

        /* Handle on hover */
        ::-webkit-scrollbar-thumb:hover {
            background: #2b302e;
            cursor: pointer;
        }
    </style>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">

    {{-- loader --}}
   <div id="loader" class="hidden">
        @include('admin.pages.loader.loader')
   </div>

    <div class="min-h-screen bg-gray-100 overflow-x-hidden">
        @include('layouts.navigation')


        <main class="">
            <div class="p-2 md:ms-[80px]">
                {{ $slot }}
            </div>
        </main>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            console.log('connected...')
            
            // var loader = $('#loader');
            // loader.removeClass('hidden'); // Show the loader

            // setTimeout(() => {
            //     loader.addClass('hidden')
            // }, 2000);

            // Get CSRF token from meta tag
            var csrfToken = $('meta[name="csrf-token"]').attr('content');

            // Get the date and time 
            const getCurrentDateTime = () => {
                var currentDate = new Date();

                // Get day, month, year
                var day = currentDate.getDate();
                var month = currentDate.toLocaleString('default', { month: 'short' });
                var year = currentDate.getFullYear();

                // Get hours and minutes
                var hours = currentDate.getHours();
                var minutes = currentDate.getMinutes();
                var ampm = hours >= 12 ? 'PM' : 'AM';

                // Convert 24-hour time to 12-hour format
                hours = hours % 12;
                hours = hours ? hours : 12; // The hour '0' should be '12'
                minutes = minutes < 10 ? '0' + minutes : minutes; // Add leading zero to minutes if needed

                // Format the date and time
                var formattedDate = day + ' ' + month + ' ' + year + ', ' + hours + ':' + minutes + ' ' + ampm;

                return formattedDate;
            }

            // display the date and time
            setInterval(() => {
                $('#date_and_time').text(getCurrentDateTime)
            }, 1000);
            // Async jQuery function with CSRF token
            const asyncRequest = (url, method, data) => {
                return new Promise(function(resolve, reject) {
                    $.ajax({
                        url: url,
                        method: method,
                        data: data,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken // Include CSRF token in the headers
                        },
                        success: function(response) {
                            resolve(response); // Resolve the promise with the response data
                        },
                        error: function(xhr, status, error) {
                            reject(error); // Reject the promise with the error
                        }
                    });
                });
            }

            // Example usage
            asyncRequest('/admin/total-request', 'GET','')
                .then(function(response) {
                    console.log(response)
                    $('.totalRequestAccount').text(response.usersRequested ? response.usersRequested : 0)
                    $('.totalGrantedAccount').text(response.usersGranted ? response.usersGranted : 0)
                })
                .catch(function(error) {
                    console.error('Error:', error);
                });
        })
    </script>

    @yield("scripts")
</body>

</html>
