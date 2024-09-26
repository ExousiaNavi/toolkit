<x-app-layout>
    <!-- Content -->
    <div class="w-full lg:ps-44 mt-2">
        <div class="bg-white border w-full p-2">

            <div class="flex justify-between items-center p-2 font-bold text-slate-600">
                <div>
                    <h1 class="text-xl capitalize">Welcome to {{ $brand }} spreadsheat management, {{ Auth::user()->name }}</h1>
                    <span class="text-sm">Manage your spreedsheet links and keywords.</span>
                </div>
                <div>
                    <a id="add_spreedsheet" class="add_spreedsheet py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-green-600 text-white hover:bg-green-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none"
                        href="#">
                        <svg class="shrink-0 size-4 text-white" xmlns="http://www.w3.org/2000/svg" width="24" viewBox="0 0 512 512" height="24" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor">
                            <!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M222.7 32.1c5 16.9-4.6 34.8-21.5 39.8C121.8 95.6 64 169.1 64 256c0 106 86 192 192 192s192-86 192-192c0-86.9-57.8-160.4-137.1-184.1c-16.9-5-26.6-22.9-21.5-39.8s22.9-26.6 39.8-21.5C434.9 42.1 512 140 512 256c0 141.4-114.6 256-256 256S0 397.4 0 256C0 140 77.1 42.1 182.9 10.6c16.9-5 34.8 4.6 39.8 21.5z"/></svg>      
                        Add Spreadsheet
                    </a>
                </div>
            </div>

            
            <div class="border">
                {{-- {{ $username }} --}}
                @include('admin.pages.tables.sp_table', ['spreedsheet_collection'=>$spreedsheet_collection])
            </div>
        </div>
    </div>

    {{-- modals collection --}}
    @include('admin.pages.modals.add_sp', ['brand'=>$brand])
    @include('admin.pages.modals.edit_sp')
    {{-- @include('admin.pages.modals.insert', ['collectionKeys'=>$collectionKeys, 'redirectedTo'=>'baji']) --}}
    @section('scripts')
        <script>
            console.log('connected baji...')
            $(document).ready(function(){
                // Get CSRF token from meta tag
                let csrfToken = $('meta[name="csrf-token"]').attr('content');
                let response = @json(session('result'));
                let statres = @json(session('status'));
                let resend = @json(session('resend'));
                let responseAction = @json(session()->all());
                let completedTime = ''
                // let testRoute = "{{ Auth::user()->role }}.test"
                console.log(responseAction)
                if(statres){
                    $m = ''
                    $i = ''
                    
                    if(statres == 'success'){
                        $m = 'Update for spreedsheet is Successfully created.'
                        $i = 'success'
                    }
                    else if(statres == 'save'){
                        $m = 'Archived for spreedsheet is Successfully.'
                        $i = 'success'
                    }else{
                         $m = 'Update for spreedsheet failed, Keywords already existed!.'
                         $i = 'error'
                    }
                    let renderHtml = `<div>
                                            <p>${$m}</p>
                                        </div>`
                                        Swal.fire({
                                            title: 'Spreedsheet Request',
                                            html: renderHtml,
                                            icon: $i,
                                            showCancelButton: false,  // Optional: If you want to show a cancel button
                                            confirmButtonText: 'Confirm',
                                            allowOutsideClick: false,  // Prevent closing when clicking outside
                                            preConfirm: () => {
                                                // Code to execute when the confirm button is clicked
                                                console.log('Confirm button clicked');
                                                
                                            }
                                        });
                }
               

                // searh on table by ip
                $('#spreed_search').on('input', function() {
                    var searchQuery = $(this).val().toLowerCase();

                    $('#spreed_table tbody tr').filter(function() {
                        $(this).toggle($(this).text().toLowerCase().indexOf(searchQuery) > -1);
                    });
                });

                const render = ($id, $sid, $spread_id, $platform, $brand, $is_active, $ctype) => {
                    let html = ''
                    let url = '{{ route('admin.manage.spreedsheet.archived', '') }}/' + $id;

                    html+= `
                    <!-- Form baji bo account -->
                    <form method="POST" action="{{ route('admin.manage.spreedsheet.edit') }}">
                    @csrf
                    <input name="id" value="${$id}" class="hidden"/>
                    <div class="grid grid-cols-3 flex-wrap gap-2 gap-y-4 mb-2">
                            <!-- Form Group -->
                            <div class="flex-1">
                            <label for="platform" class="block text-sm mb-2 dark:text-white">Platform</label>
                            <div class="relative">
                                <!-- Select -->
                                <select name="platform" class="py-3 px-3 pe-9 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600">
                                    
                                    <option value="Richads" ${$platform == 'Richads' ? 'selected' : ''}>Richads</option>
                                    <option value="TrafficStars" ${$platform == 'TrafficStars' ? 'selected' : ''}>TrafficStars</option>
                                    <option value="Adcash" ${$platform == 'Adcash' ? 'selected' : ''}>Adcash</option>
                                    <option value="TrafficNomads" ${$platform == 'TrafficNomads' ? 'selected' : ''}>TrafficNomads</option>
                                    <option value="Adsterra" ${$platform == 'Adsterra' ? 'selected' : ''}>Adsterra</option>
                                    <option value="FlatAd" ${$platform == 'FlatAd' ? 'selected' : ''}>FlatAd</option>
                                    <option value="ADxAD" ${$platform == 'ADxAD' ? 'selected' : ''}>ADxAD</option>
                                    <option value="Exoclick" ${$platform == 'Exoclick' ? 'selected' : ''}>Exoclick</option>
                                    <option value="PropellerAds" ${$platform == 'PropellerAds' ? 'selected' : ''}>PropellerAds</option>
                                    <option value="ClickAdu" ${$platform == 'ClickAdu' ? 'selected' : ''}>ClickAdu</option>
                                    <option value="HilltopAds" ${$platform == 'HilltopAds' ? 'selected' : ''}>HilltopAds</option>
                                    <option value="Trafficforce" ${$platform == 'Trafficforce' ? 'selected' : ''}>Trafficforce</option>
                                    <option value="AdMaven" ${$platform == 'AdMaven' ? 'selected' : ''}>AdMaven</option>
                                    <option value="DaoAD" ${$platform == 'DaoAD' ? 'selected' : ''}>DaoAD</option>
                                    <option value="Onclicka" ${$platform == 'Onclicka' ? 'selected' : ''}>Onclicka</option>
                                    <option value="TrafficShop" ${$platform == 'TrafficShop' ? 'selected' : ''}>TrafficShop</option>
                                </select>
                                
                            <!-- End Select -->
                            </div>
                            </div>
                            <!-- End Form Group -->
            
                            <!-- Form Group -->
                            <div class="flex-1">
                            <div class="flex justify-between items-center">
                                <label for="keyword" class="block text-sm mb-2 dark:text-white">BO Keyword</label>
                            </div>
                            <div class="relative">
                                <input type="text" value="${$sid}" name="keyword" class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-800 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600" required aria-describedby="password-error" required>
                                <div class="hidden absolute inset-y-0 end-0 pointer-events-none pe-3">
                                <svg class="size-5 text-red-500" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
                                </svg>
                                </div>
                            </div>
                            </div>
                            <!-- End Form Group -->
                            <!-- Form Group -->
                                <div class="">
                                <div class="flex justify-between items-center">
                                    <label for="currenctType" class="block text-sm mb-2 dark:text-white">Currency Type</label>
                                </div>
                                <div class="relative">
                                    <!-- Select -->
                                    <select name="currencyType" class="py-3 px-3 pe-9 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600" required>         
                                    <option value=""></option>
                                    <option value="-1|all" ${$ctype == '-1|all' ? 'selected' : ''}>all</option>
                                    <option value="2|VND" ${$ctype == '2|VND' ? 'selected' : ''}>VND</option>
                                    <option value="8|BDT" ${$ctype == '8|BDT' ? 'selected' : ''}>BDT</option>
                                    <option value="15|USD" ${$ctype == '15|USD' ? 'selected' : ''}>USD</option>
                                    <option value="15|KHR" ${$ctype == '15|USD' ? 'selected' : ''}>KHR</option>
                                    <option value="7|INR" ${$ctype == '7|INR' ? 'selected' : ''}>INR</option>
                                    <option value="17|PKR" ${$ctype == '17|PKR' ? 'selected' : ''}>PKR</option>
                                    <option value="16|PHP" ${$ctype == '16|PHP' ? 'selected' : ''}>PHP</option>
                                    <option value="5|KRW" ${$ctype == '5|KRW' ? 'selected' : ''}>KRW</option>
                                    <option value="6|IDR" ${$ctype == '6|IDR' ? 'selected' : ''}>IDR</option>
                                    <option value="24|NPR" ${$ctype == '24|NPR' ? 'selected' : ''}>NPR</option>
                                    <option value="9|THB" ${$ctype == '9|THB' ? 'selected' : ''}>THB</option>
                                    <option value="25|CAD" ${$ctype == '25|CAD' ? 'selected' : ''}>CAD</option>
                                    <option value="11|HKD" ${$ctype == '11|HKD' ? 'selected' : ''}>HKD</option>
                                    <option value="1|MYR" ${$ctype == '1|MYR' ? 'selected' : ''}>MYR</option>
                                    <option value="4|SGD" ${$ctype == '4|SGD' ? 'selected' : ''}>SGD</option>
                                </select>
                                <!-- End Select -->
                                </div>
                                </div>

                            

                            <div class="flex-1">
                            <label for="is_active" class="block text-sm mb-2 dark:text-white">Is Active?</label>
                            <div class="relative">
                                
                                <!-- Select -->
                                <select name="is_active" class="py-3 px-3 pe-9 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600">
                                    
                                    <option value="1" ${$is_active == true ? 'selected' : ''}>True</option>
                                    <option value="0" ${$is_active == false ? 'selected' : ''}>False</option>
                                </select>
                                <!-- End Select -->
                            </div>
                            </div>

                            <!-- Form Group -->
                            <div class="col-span-2">
                            <div class="flex justify-between items-center">
                                <label for="password" class="block text-sm mb-2 dark:text-white">Spreedsheet Link </label>
                            </div>
                            <div class="relative">
                                <input type="text" name="link" value="https://docs.google.com/spreadsheets/d/${$spread_id}/edit?gid=379053111" class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-800 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600" required aria-describedby="password-error" required>
                                <div class="hidden absolute inset-y-0 end-0 pointer-events-none pe-3">
                                <svg class="size-5 text-red-500" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
                                </svg>
                                </div>
                            </div>
                            </div>
                            <!-- End Form Group -->

                            <!-- Form Group -->
                            <div class="flex justify-center items-center">
                            
                            <div class="w-full">
                                {{-- <input type="password" name="password" class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-800 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600" required aria-describedby="password-error"> --}}
                                <button type="submit" class="p-2 py-3 w-full inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-green-600 text-white hover:bg-green-700 focus:outline-none focus:bg-green-700 disabled:opacity-50 disabled:pointer-events-none">Save Spreedsheet</button>
                            
                            </div>
                            </div>
                            <!-- End Form Group -->
                            <!-- Form Group -->
                            <div class="flex justify-center items-center">
                            
                            <div class="w-full">
                                <a href="${url}" class="p-2 py-3 w-full inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:bg-red-700 disabled:opacity-50 disabled:pointer-events-none">Archived Spreedsheet</a>
                            
                            </div>
                            </div>
                            <!-- End Form Group -->
                        </div>
                    </form>
                    <!-- End Form -->
                        
                    `

                    $('#renderEditForm').html(html)
                }

                $('.sp_edit').click(function(){
                    // alert($(this).data('id'))
                    $id = $(this).data('id')
                    $sid = $(this).data('sid')
                    $spread_id = $(this).data('spread_id')
                    $platform = $(this).data('platform')
                    $brand = $(this).data('brand')
                    $is_active = $(this).data('is_active')
                    $type = $(this).data('type')
                    console.log($platform)
                    render($id, $sid, $spread_id, $platform, $brand, $is_active,$type)
                    $('.ed_sp').trigger('click')

                })

                $('.add_spreedsheet').click(function(){
                    $('.insert_sp').trigger('click')
                })
                
            })
        </script>
    @endsection
</x-app-layout>