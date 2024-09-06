use App\Http\Controllers\CtnController;

<x-app-layout>
    <!-- Content -->
    <div class="w-full lg:ps-44 mt-2">
        <div class="bg-white border w-full p-2">

            <div class="flex justify-between items-center p-2 font-bold text-slate-600">
                <div>
                    <h1 class="text-xl capitalize">Welcome to CTN report, {{ Auth::user()->name }}</h1>
                    <span class="text-sm">This is the available currency for automation.</span>
                </div>
                <div>
                    <a id="ctn_automate" class="ctn_automate py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none"
                        href="#">
                        <svg class="shrink-0 size-4 text-white" xmlns="http://www.w3.org/2000/svg" width="24" viewBox="0 0 512 512" height="24" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor">
                            <!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M222.7 32.1c5 16.9-4.6 34.8-21.5 39.8C121.8 95.6 64 169.1 64 256c0 106 86 192 192 192s192-86 192-192c0-86.9-57.8-160.4-137.1-184.1c-16.9-5-26.6-22.9-21.5-39.8s22.9-26.6 39.8-21.5C434.9 42.1 512 140 512 256c0 141.4-114.6 256-256 256S0 397.4 0 256C0 140 77.1 42.1 182.9 10.6c16.9-5 34.8 4.6 39.8 21.5z"/></svg>      
                        Automate All
                    </a>
                    <a id="ctn_automate_report" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none"
                        href="#">
                        <svg class="shrink-0 size-4 text-white" xmlns="http://www.w3.org/2000/svg" width="24" viewBox="0 0 512 512" height="24" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor">
                            <!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M222.7 32.1c5 16.9-4.6 34.8-21.5 39.8C121.8 95.6 64 169.1 64 256c0 106 86 192 192 192s192-86 192-192c0-86.9-57.8-160.4-137.1-184.1c-16.9-5-26.6-22.9-21.5-39.8s22.9-26.6 39.8-21.5C434.9 42.1 512 140 512 256c0 141.4-114.6 256-256 256S0 397.4 0 256C0 140 77.1 42.1 182.9 10.6c16.9-5 34.8 4.6 39.8 21.5z"/></svg>      
                        Merge to excel
                    </a>

                    {{-- <a id="baji_add_currency" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none"
                        href="#">
                            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24"
                                height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14" />
                            <path d="M12 5v14" />
                        </svg>
                        Add currency
                    </a> --}}
                </div>
            </div>

            <div class="p-2 flex flex-wrap gap-2">

                {{-- {{ $currencies }} --}}
                @foreach ($currencies as $currency)
                    <div class="border shadow rounded-sm p-2 max-w-[250px]">
                        <div class="flex-1 md:flex-none flex hover:cursor-pointer hover:scale-105 transform transition-transform duration-300">
                            <div>
                                <svg class="text-green-800 shrink-0 size-20" xmlns="http://www.w3.org/2000/svg" width="24"
                                            height="24" viewBox="0 0 384 512" fill="currentColor" stroke="currentColor" viewBox="0 0 384 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M48 448L48 64c0-8.8 7.2-16 16-16l160 0 0 80c0 17.7 14.3 32 32 32l80 0 0 288c0 8.8-7.2 16-16 16L64 464c-8.8 0-16-7.2-16-16zM64 0C28.7 0 0 28.7 0 64L0 448c0 35.3 28.7 64 64 64l256 0c35.3 0 64-28.7 64-64l0-293.5c0-17-6.7-33.3-18.7-45.3L274.7 18.7C262.7 6.7 246.5 0 229.5 0L64 0zm90.9 233.3c-8.1-10.5-23.2-12.3-33.7-4.2s-12.3 23.2-4.2 33.7L161.6 320l-44.5 57.3c-8.1 10.5-6.3 25.5 4.2 33.7s25.5 6.3 33.7-4.2L192 359.1l37.1 47.6c8.1 10.5 23.2 12.3 33.7 4.2s12.3-23.2 4.2-33.7L222.4 320l44.5-57.3c8.1-10.5 6.3-25.5-4.2-33.7s-25.5-6.3-33.7 4.2L192 280.9l-37.1-47.6z"/></svg>
                            </div>
                            <div class="bg-slate-100 rounded-md p-2 flex flex-col">
                                <span class="font-bold text-slate-700">{{ $currency->currency }} - Currency</span>
                                
                                @if (in_array($currency->currency, $completedTask))
                                    <a data-c_type="{{ $currency->currency }}" class="ctn_automate_completed py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-green-50 text-green-500 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none"
                                        >
                                        <svg class="shrink-0 size-4 text-green-500" xmlns="http://www.w3.org/2000/svg" width="24" viewBox="0 0 512 512" height="24" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor">
                                            <!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M222.7 32.1c5 16.9-4.6 34.8-21.5 39.8C121.8 95.6 64 169.1 64 256c0 106 86 192 192 192s192-86 192-192c0-86.9-57.8-160.4-137.1-184.1c-16.9-5-26.6-22.9-21.5-39.8s22.9-26.6 39.8-21.5C434.9 42.1 512 140 512 256c0 141.4-114.6 256-256 256S0 397.4 0 256C0 140 77.1 42.1 182.9 10.6c16.9-5 34.8 4.6 39.8 21.5z"/></svg>      
                                        Completed
                                    </a>
                                @else   
                                    <a data-c_type="{{ $currency->currency }}" class="ctn_automate_debug py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-orange-100 text-orange-600 hover:bg-orange-200 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none"
                                        >
                                        <svg class="shrink-0 size-4 text-orange-300" xmlns="http://www.w3.org/2000/svg" width="24" viewBox="0 0 512 512" height="24" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor">
                                            <!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M222.7 32.1c5 16.9-4.6 34.8-21.5 39.8C121.8 95.6 64 169.1 64 256c0 106 86 192 192 192s192-86 192-192c0-86.9-57.8-160.4-137.1-184.1c-16.9-5-26.6-22.9-21.5-39.8s22.9-26.6 39.8-21.5C434.9 42.1 512 140 512 256c0 141.4-114.6 256-256 256S0 397.4 0 256C0 140 77.1 42.1 182.9 10.6c16.9-5 34.8 4.6 39.8 21.5z"/></svg>      
                                        Pending
                                    </a>
                                @endif
                                
                            </div>
                            
                        </div>
                        
                    </div>
                @endforeach

            </div>
            <div class="border">
                {{-- {{ $username }} --}}
                @include('admin.pages.tables.ctn_table', ['bos'=>$bo, 'usernames'=>$username, 'platforms'=>$platforms])
            </div>
        </div>
    </div>

    @section('scripts')
        <script>
            console.log('connected ctn...')
            $(document).ready(function(){
                // Get CSRF token from meta tag
                let csrfToken = $('meta[name="csrf-token"]').attr('content');
                let response = @json(session('result'));
                let responseAction = @json(session()->all());
                let completedTime = ''
                // let testRoute = "{{ Auth::user()->role }}.test"
                console.log(responseAction)

                
                const popup = (status,title, text, icon, data) => {
                    
                    if(status !== null){
                        let renderHtml = `<div>
                                            <p><strong>${text}</strong></p>
                                            <p>${data ? data : ''}</p>
                                        </div>`
                                        Swal.fire({
                                            title: title,
                                            html: renderHtml,
                                            icon: icon,
                                            showCancelButton: false,  // Optional: If you want to show a cancel button
                                            confirmButtonText: 'Confirm',
                                            allowOutsideClick: false,  // Prevent closing when clicking outside
                                            preConfirm: () => {
                                                // Code to execute when the confirm button is clicked
                                                console.log('Confirm button clicked');
                                                // Perform any action here
                                                window.location.reload()
                                            }
                                        });
                    }
                    
                }

                const popup2 = (status, title, text, icon, elapseTime,res, stat) => {
                    let renderList = '';
                    console.log(res)
                    if (stat) {
                        res.slice(1).forEach(r => {
                            // Ensure 'r' is not null and contains the required properties ('time', 'text', 'keyword')
                            if (r && r.time && r.text && r.keyword) {
                                renderList += `
                                <div class="flex gap-x-3">
                                        <div class="w-16 text-end">
                                            <span class="text-xs text-gray-500 dark:text-neutral-400">${r.time}</span>
                                        </div>
                                        <div class="relative last:after:hidden after:absolute after:top-7 after:bottom-0 after:start-3.5 after:w-px after:-translate-x-[0.5px] after:bg-gray-200 dark:after:bg-neutral-700">
                                            <div class="relative z-10 size-7 flex justify-center items-center">
                                                <div class="size-2 rounded-full bg-gray-400 dark:bg-neutral-600"></div>
                                            </div>
                                        </div>
                                        <div class="pt-0.5 pb-4 text-start">
                                            <h3 class="flex gap-x-1.5 font-semibold text-gray-800 dark:text-white">${r.text}</h3>
                                            <button type="button" class="mt-1 -ms-1 p-1 inline-flex items-center gap-x-2 text-xs rounded-lg border border-transparent text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none dark:text-neutral-400 dark:hover:bg-neutral-700 dark:focus:bg-neutral-700">
                                                <svg class="shrink-0 size-4 mt-0.5 text-teal-500" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                                </svg>
                                                ${r.keyword}
                                            </button>
                                        </div>
                                    </div>
                                `;
                            }
                        });
                    }

                    if (status !== null) {
                        let renderHtml = `<div class="max-h-[400px] shadow overflow-y-auto">
                                            <p class="bg-green-500 text-white"><strong>${elapseTime}</strong></p>
                                            <div>${renderList}</div>
                                        </div>`;
                        
                        Swal.fire({
                            title: title,
                            html: renderHtml,
                            icon: icon,
                            showCancelButton: false,
                            // confirmButtonText: 'Confirm',
                            allowOutsideClick: false,
                            // preConfirm: () => {
                            //     console.log('Confirm button clicked');
                            //     window.location.reload();
                            // }
                        });
                    }
                }

                //loader 
                const loader = (htmls) => {
                    // Store the start time
                    const startTime = Date.now();
                    
                    // Store the Swal instance so you can close it later
                    const swalInstance = Swal.fire({
                        title: "Automation is in progress!",
                        html: htmls,
                        timerProgressBar: true,
                        allowOutsideClick: false,  // Prevent closing when clicking outside
                        didOpen: () => {
                            Swal.showLoading();
                            const timer = Swal.getPopup().querySelector("b");
                             // Start interval to update the elapsed time
                            timerInterval = setInterval(() => {
                                // const elapsedTime = Math.floor((Date.now() - startTime) / 1000); // Calculate elapsed time in seconds
                                // timer.textContent = `Elapsed time: ${elapsedTime} seconds`;
                                const elapsedTime = Math.floor((Date.now() - startTime) / 1000); // Calculate elapsed time in seconds
    
                                const hours = Math.floor(elapsedTime / 3600);
                                const minutes = Math.floor((elapsedTime % 3600) / 60);
                                const seconds = elapsedTime % 60;
                                
                                timer.textContent = `Elapsed time: ${hours}h ${minutes}m ${seconds}s`;
                                completedTime = `Elapsed time: ${hours}h ${minutes}m ${seconds}s`
                            }, 1000);  // Update every second
                        },
                        willClose: () => {
                            clearInterval(timerInterval);
                        }
                    });

                    return swalInstance;
                };

                // To close the loader programmatically
                const closeLoader = () => {
                    Swal.close();
                };

                 // Async jQuery function with CSRF token
                const asyncRequest = (url, method, data, message) => {
                    return new Promise(function(resolve, reject) {
                        // Show loader
                        const swalInstance = loader(`<div class="bg-slate-100 p-1 flex flex-col gap-2">
                                    <span class="text-green-500">${message}...</span>
                                    <span class="text-slate-500"><b></b></span>
                                </div>`);
                        $.ajax({
                            url: url,
                            method: method,
                            data: data,
                            headers: {
                                'X-CSRF-TOKEN': csrfToken // Include CSRF token in the headers
                            },
                            success: function(response) {
                                resolve(response); // Resolve the promise with the response data
                                // This can be called from anywhere in your code
                                closeLoader();
                            },
                            error: function(xhr, status, error) {
                               // Try to parse the response as JSON if available
                                let errorResponse = xhr.responseText;
                                try {
                                    errorResponse = JSON.parse(xhr.responseText); // Parse the JSON error response
                                } catch (e) {
                                    console.error('Could not parse error response as JSON:', xhr.responseText);
                                }

                                reject({
                                    status: xhr.status,
                                    statusText: xhr.statusText,
                                    error: errorResponse
                                }); // Reject the promise with the full error details
                                closeLoader(); // Close the loader on error
                            }
                        });
                    });
                }

                
                //filters
                $('.f_currency').click(function() {
                    var filterQuery = $(this).data('name').toLowerCase();

                    if (filterQuery != 'all') {
                        $('#ctn_table tbody tr').each(function() {
                            var rowText = $(this).text().toLowerCase();
                            if (rowText.indexOf(filterQuery) > -1) {
                                $(this).show();  // Show the row if it matches the filter
                            } else {
                                $(this).hide();  // Hide the row if it doesn't match the filter
                            }
                        });
                    } else {
                        // Show all rows if 'all' is selected
                        $('#ctn_table tbody tr').show();
                    }
                });


                //searching
                // searh on table by ip
                $('#ctn_search').on('input', function() {
                    var searchQuery = $(this).val().toLowerCase();

                    $('#ctn_table tbody tr').filter(function() {
                        $(this).toggle($(this).text().toLowerCase().indexOf(searchQuery) > -1);
                    });
                });
            
                // test for automating btd
                $('.ctn_automate').click(async function() {
                    // Array of currencies
                    let currencies = ['HKD', 'MYR', 'SGD'];

                    // popup('completed', `No active reports available.`, 'The automation process has been completed successfully and will be available again tomorrow.', 'info', '')

                    for (const currency of currencies) {
                        try {
                            completedTime = '';
                            
                            // Await the async request
                            let response = await asyncRequest(`/admin/ctn/bo`, 'POST', { 'currency': currency }, `Connecting to BO and FE platform to fetch data for ${currency}`);
                            
                            // Handle success response
                            let result = response.result.data;
                            popup(result.status, result.title, result.text, result.icon, completedTime);
                            console.log(`Successfully processed: ${currency}`);
                            
                            // Wait for 3 seconds before closing the popup and moving to the next request
                            await new Promise(resolve => setTimeout(resolve, 3000));  // 3 second delay
                            
                        } catch (error) {
                            // Handle error response
                            console.error(`Error processing ${currency}:`, error.error.result);
                            if (!error.error.result.success) {
                                popup("error", "Connection Problem.", error.error.result.error, 'error', completedTime);
                                
                                // Wait for 3 seconds after the error popup before moving to the next request
                                await new Promise(resolve => setTimeout(resolve, 3000));  // 3 second delay
                            }
                        }
                    }
                });


                //merge report on spreedsheet
                $('#ctn_automate_report').click(function(){
                    completedTime = ''
                    asyncRequest(`/admin/spreedsheet`, 'POST',{'currency' : ''}, 'Connecting to Spreadsheet to transfer data')
                        .then(function(response) {
                            console.log(response.result)
                            let result = response.result.data
                            popup2(result[0].status, result[0].title, result[0].text, result[0].icon, completedTime, result, true)
                        })
                        .catch(function(error) {
                            console.error('Error:', error);
                        });
                })
                // trigger add brand modal
                // $('#baji_add_currency').click(function(){
                //     // alert('yes')
                //     $('#add_currency').trigger('click')
                // })

                // trigger completed task button
                $('.ctn_automate_completed').click(function(){
                    let c = $(this).data('c_type')
                    popup('completed', `${c} Completed.`, 'Automation is already been completed, available again tomorrow.', 'info', '')
                })

                
                if(responseAction.status != undefined){
                    popup(responseAction.status, responseAction.title, responseAction.text, responseAction.icon, '')
                }
                

            })
        </script>
    @endsection
</x-app-layout>