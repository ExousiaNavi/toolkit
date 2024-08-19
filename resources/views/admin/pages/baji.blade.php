<x-app-layout>
    <!-- Content -->
    <div class="w-full lg:ps-44 mt-2">
        <div class="bg-white border w-full p-2">

            <div class="flex justify-between items-center p-2 font-bold text-slate-600">
                <div>
                    <h1 class="text-xl capitalize">Welcome to BAJI report, {{ Auth::user()->name }}</h1>
                    <span class="text-sm">This is the available currency for automation.</span>
                </div>
                <div>
                    <a id="baji_automate_all" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none"
                        href="#">
                        <svg class="shrink-0 size-4 text-white" xmlns="http://www.w3.org/2000/svg" width="24" viewBox="0 0 512 512" height="24" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor">
                            <!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M222.7 32.1c5 16.9-4.6 34.8-21.5 39.8C121.8 95.6 64 169.1 64 256c0 106 86 192 192 192s192-86 192-192c0-86.9-57.8-160.4-137.1-184.1c-16.9-5-26.6-22.9-21.5-39.8s22.9-26.6 39.8-21.5C434.9 42.1 512 140 512 256c0 141.4-114.6 256-256 256S0 397.4 0 256C0 140 77.1 42.1 182.9 10.6c16.9-5 34.8 4.6 39.8 21.5z"/></svg>      
                        Automate all
                    </a>

                    <a id="baji_add_report" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none"
                        href="#">
                            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24"
                                height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14" />
                            <path d="M12 5v14" />
                        </svg>
                        Add report
                    </a>
                </div>
            </div>

            <div class="p-2 flex flex-wrap gap-2">

                <div class="border flex-1 md:flex-none shadow rounded-sm p-2 flex hover:cursor-pointer hover:scale-105 transform transition-transform duration-300">
                    <div>
                        <svg class="text-green-800 shrink-0 size-20" xmlns="http://www.w3.org/2000/svg" width="24"
                                    height="24" viewBox="0 0 384 512" fill="currentColor" stroke="currentColor" viewBox="0 0 384 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M48 448L48 64c0-8.8 7.2-16 16-16l160 0 0 80c0 17.7 14.3 32 32 32l80 0 0 288c0 8.8-7.2 16-16 16L64 464c-8.8 0-16-7.2-16-16zM64 0C28.7 0 0 28.7 0 64L0 448c0 35.3 28.7 64 64 64l256 0c35.3 0 64-28.7 64-64l0-293.5c0-17-6.7-33.3-18.7-45.3L274.7 18.7C262.7 6.7 246.5 0 229.5 0L64 0zm90.9 233.3c-8.1-10.5-23.2-12.3-33.7-4.2s-12.3 23.2-4.2 33.7L161.6 320l-44.5 57.3c-8.1 10.5-6.3 25.5 4.2 33.7s25.5 6.3 33.7-4.2L192 359.1l37.1 47.6c8.1 10.5 23.2 12.3 33.7 4.2s12.3-23.2 4.2-33.7L222.4 320l44.5-57.3c8.1-10.5 6.3-25.5-4.2-33.7s-25.5-6.3-33.7 4.2L192 280.9l-37.1-47.6z"/></svg>
                    </div>
                    <div class="bg-slate-100 rounded-md p-2 flex flex-col">
                        <span class="font-bold text-slate-700">BDT - Currency</span>
                        <a id="btd_automate" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none"
                            >
                            <svg class="shrink-0 size-4 text-white" xmlns="http://www.w3.org/2000/svg" width="24" viewBox="0 0 512 512" height="24" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor">
                                <!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M222.7 32.1c5 16.9-4.6 34.8-21.5 39.8C121.8 95.6 64 169.1 64 256c0 106 86 192 192 192s192-86 192-192c0-86.9-57.8-160.4-137.1-184.1c-16.9-5-26.6-22.9-21.5-39.8s22.9-26.6 39.8-21.5C434.9 42.1 512 140 512 256c0 141.4-114.6 256-256 256S0 397.4 0 256C0 140 77.1 42.1 182.9 10.6c16.9-5 34.8 4.6 39.8 21.5z"/></svg>      
                            Automate
                        </a>
                    </div>
                </div>

                {{-- completed UI --}}
                <div class="border flex-1 md:flex-none shadow rounded-sm p-2 flex hover:cursor-pointer hover:scale-105 transform transition-transform duration-300">
                    <div>
                        <svg class="text-green-800 shrink-0 size-20" xmlns="http://www.w3.org/2000/svg" width="24"
                                    height="24" viewBox="0 0 384 512" fill="currentColor" stroke="currentColor" viewBox="0 0 384 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M48 448L48 64c0-8.8 7.2-16 16-16l160 0 0 80c0 17.7 14.3 32 32 32l80 0 0 288c0 8.8-7.2 16-16 16L64 464c-8.8 0-16-7.2-16-16zM64 0C28.7 0 0 28.7 0 64L0 448c0 35.3 28.7 64 64 64l256 0c35.3 0 64-28.7 64-64l0-293.5c0-17-6.7-33.3-18.7-45.3L274.7 18.7C262.7 6.7 246.5 0 229.5 0L64 0zm90.9 233.3c-8.1-10.5-23.2-12.3-33.7-4.2s-12.3 23.2-4.2 33.7L161.6 320l-44.5 57.3c-8.1 10.5-6.3 25.5 4.2 33.7s25.5 6.3 33.7-4.2L192 359.1l37.1 47.6c8.1 10.5 23.2 12.3 33.7 4.2s12.3-23.2 4.2-33.7L222.4 320l44.5-57.3c8.1-10.5 6.3-25.5-4.2-33.7s-25.5-6.3-33.7 4.2L192 280.9l-37.1-47.6z"/></svg>
                    </div>
                    <div class="bg-slate-100 rounded-md p-2 flex flex-col">
                        <span class="font-bold text-slate-700">PKR - Currency</span>
                        <a id="baji_automate" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-green-600 text-white hover:bg-green-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none"
                            href="#">
                            <svg class="shrink-0 size-4 text-white" xmlns="http://www.w3.org/2000/svg" width="24" viewBox="0 0 512 512" height="24" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor">
                                <!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M222.7 32.1c5 16.9-4.6 34.8-21.5 39.8C121.8 95.6 64 169.1 64 256c0 106 86 192 192 192s192-86 192-192c0-86.9-57.8-160.4-137.1-184.1c-16.9-5-26.6-22.9-21.5-39.8s22.9-26.6 39.8-21.5C434.9 42.1 512 140 512 256c0 141.4-114.6 256-256 256S0 397.4 0 256C0 140 77.1 42.1 182.9 10.6c16.9-5 34.8 4.6 39.8 21.5z"/></svg>      
                            Completed
                        </a>
                    </div>
                </div>

                {{-- Failed UI --}}
                <div class="border flex-1 md:flex-none shadow rounded-sm p-2 flex hover:cursor-pointer hover:scale-105 transform transition-transform duration-300">
                    <div>
                        <svg class="text-green-800 shrink-0 size-20" xmlns="http://www.w3.org/2000/svg" width="24"
                                    height="24" viewBox="0 0 384 512" fill="currentColor" stroke="currentColor" viewBox="0 0 384 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M48 448L48 64c0-8.8 7.2-16 16-16l160 0 0 80c0 17.7 14.3 32 32 32l80 0 0 288c0 8.8-7.2 16-16 16L64 464c-8.8 0-16-7.2-16-16zM64 0C28.7 0 0 28.7 0 64L0 448c0 35.3 28.7 64 64 64l256 0c35.3 0 64-28.7 64-64l0-293.5c0-17-6.7-33.3-18.7-45.3L274.7 18.7C262.7 6.7 246.5 0 229.5 0L64 0zm90.9 233.3c-8.1-10.5-23.2-12.3-33.7-4.2s-12.3 23.2-4.2 33.7L161.6 320l-44.5 57.3c-8.1 10.5-6.3 25.5 4.2 33.7s25.5 6.3 33.7-4.2L192 359.1l37.1 47.6c8.1 10.5 23.2 12.3 33.7 4.2s12.3-23.2 4.2-33.7L222.4 320l44.5-57.3c8.1-10.5 6.3-25.5-4.2-33.7s-25.5-6.3-33.7 4.2L192 280.9l-37.1-47.6z"/></svg>
                    </div>
                    <div class="bg-slate-100 rounded-md p-2 flex flex-col">
                        <span class="font-bold text-slate-700">HKR - Currency</span>
                        <button id="hkr_automate" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none"
                            href="#">
                            <svg class="shrink-0 size-4 text-white" xmlns="http://www.w3.org/2000/svg" width="24" viewBox="0 0 512 512" height="24" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor">
                                <!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M222.7 32.1c5 16.9-4.6 34.8-21.5 39.8C121.8 95.6 64 169.1 64 256c0 106 86 192 192 192s192-86 192-192c0-86.9-57.8-160.4-137.1-184.1c-16.9-5-26.6-22.9-21.5-39.8s22.9-26.6 39.8-21.5C434.9 42.1 512 140 512 256c0 141.4-114.6 256-256 256S0 397.4 0 256C0 140 77.1 42.1 182.9 10.6c16.9-5 34.8 4.6 39.8 21.5z"/></svg>      
                            Failed
                        </button>
                    </div>
                </div>

            </div>

        </div>
    </div>

    @section('scripts')
        <script>
            console.log('connected baji...')
            $(document).ready(function(){
                // Get CSRF token from meta tag
                let csrfToken = $('meta[name="csrf-token"]').attr('content');
                let response = @json(session('result'));
                // let testRoute = "{{ Auth::user()->role }}.test"
                console.log(response)

                const popup = (status,title, text, icon, data) => {
                    if(status !== null){
                        let renderHtml = `<div>
                                            <p><strong>${text}</strong></p>
                                            <p>${data}</p>
                                        </div>`
                        Swal.fire({
                            title: title,
                            html: renderHtml,
                            icon: icon
                        });
                    }
                    
                }

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

                // Connecting Request to a server
                asyncRequest('http://127.0.0.1:8081/', 'GET','')
                    .then(function(response) {
                        // console.log(response)
                        popup(response.status, response.title, response.text, response.icon, '')
                    })
                    .catch(function(error) {
                        console.error('Error:', error);
                    });


            
                // test for automating btd
                $('#btd_automate').click(function(){
                    asyncRequest(`/admin/test`, 'GET','')
                        .then(function(response) {
                            console.log(response.result)
                            let result = response.result.data
                            popup(result.status, result.title, result.text, result.icon, result.data)
                        })
                        .catch(function(error) {
                            console.error('Error:', error);
                        });
                })
            })
        </script>
    @endsection
</x-app-layout>