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
        @include('admin.pages.modals.account')
    </div>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            console.log('connected...')
            $modal = @json(session('modal'));
            console.log($modal)

            const successModal = () => {
                Swal.fire({
                title: "Updated Account!",
                text: "You updated the bo accounts!",
                icon: "success"
                });
            }

            if($modal) successModal();
            // $('.btnAccounts').trigger('click')

            $('#boaccount').click(function(){
                $('.btnAccounts').trigger('click')
            })

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

            //render accounts
            const renderAccounts = (accounts) => {
                // console.log()
                let html = ''
                accounts.forEach(acc => {
                    // console.log(acc)
                    html += `
                        <div class="bg-slate-100 rounded-md mb-2 p-2">
                            <form action="{{ route('admin.manage.account') }}" method="POST">
                            @csrf
                            <input type="text" name="brand" value="${acc.brand}" class="hidden">
                            <div class="py-3 flex items-center text-xs text-gray-400 uppercase before:flex-1 before:border-t before:border-gray-200 before:me-6 after:flex-1 after:border-t after:border-gray-200 after:ms-6 dark:text-neutral-500 dark:before:border-neutral-800 dark:after:border-neutral-800">${acc.brand}</div>

                            <div class="flex gap-2 gap-y-4 mb-2">
                                
                                <!-- Form Group -->
                                <div class="flex-1">
                                <label for="username" class="block text-sm mb-2 dark:text-white">Username</label>
                                <div class="relative">
                                    <input type="text" name="username" value="${acc.email}" class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-800 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600" required aria-describedby="email-error">
                                    <div class="hidden absolute inset-y-0 end-0 pointer-events-none pe-3">
                                    <svg class="size-5 text-red-500" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
                                    </svg>
                                    </div>
                                </div>
                                </div>
                                <!-- End Form Group -->
                
                                <!-- Form Group -->
                                <div class="flex-1">
                                <label class="block text-sm mb-2 dark:text-white">Password</label>
                                <div class="relative">
                                    <input name="password" data-name="hs-toggle-password-${acc.brand}" id="hs-toggle-password-${acc.brand}" type="password" class="py-3 ps-4 pe-10 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600" placeholder="Enter password" value="${acc.password}">
                                    <button data-name="hs-toggle-password-${acc.brand}" type="button" class="togglepassword absolute inset-y-0 end-0 flex items-center z-20 px-3 cursor-pointer text-gray-400 rounded-e-md focus:outline-none focus:text-blue-600 dark:text-neutral-600 dark:focus:text-blue-500">
                                    <svg class="shrink-0 size-3.5" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path class="hs-password-active:hidden" d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                                        <path class="hs-password-active:hidden" d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path>
                                        <path class="hs-password-active:hidden" d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path>
                                        <line class="hs-password-active:hidden" x1="2" x2="22" y1="2" y2="22"></line>
                                        <path class="hidden hs-password-active:block" d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                                        <circle class="hidden hs-password-active:block" cx="12" cy="12" r="3"></circle>
                                    </svg>
                                    </button>
                                </div>
                                </div>
                                <!-- End Form Group -->
                                <!-- Form Group -->
                                <div class="flex justify-center items-center">
                                
                                    <div class="relative -bottom-3.5">
                                        <button type="submit" class="p-2 py-3 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">Update</button>
                                    </div>
                                </div>
                                <!-- End Form Group -->
                            </div>
                            
                        </form>    
                        </div>
                    `
                });

                $('.renderContainer').html(html)

                //toogle password
                $('.togglepassword').click(function() {
                    var inputField = $(`#${$(this).data('name')}`);
                    var currentType = inputField.attr('type');

                    if (currentType === 'password') {
                        inputField.attr('type', 'text');
                    } else {
                        inputField.attr('type', 'password');
                    }
                });

            }

            
            
            // Example usage
            asyncRequest('/admin/total-request', 'GET','')
                .then(function(response) {
                    // console.log(response.boAccounts)
                    renderAccounts(response.boAccounts)
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
