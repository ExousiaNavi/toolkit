<div class="text-center">
    <button id="insert_clicks" type="button"
        class="hidden py-3 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none"
        aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-modal-signin" data-hs-overlay="#hs-modal-currency">
        Open modal
    </button>
</div>

<div id="hs-modal-currency" class="hs-overlay hidden size-full fixed top-0 start-0 z-[80] overflow-x-hidden overflow-y-auto"
    role="dialog" tabindex="-1" aria-labelledby="hs-modal-signin-label">
    <div
        class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-3xl sm:w-full m-3 sm:mx-auto">
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-neutral-900 dark:border-neutral-800">
            <div class="p-4 sm:p-7">
                <div class="text-center">
                    <h3 id="hs-modal-currency-label" class="block text-2xl font-bold text-gray-800 dark:text-neutral-200">
                        Cost, Impression and Clicks 
                    </h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-neutral-400">
                        Add a cost, impressions and clicks here?
                    </p>
                </div>

                <div class="mt-5">


                    
                    {{-- {{ $collectionKeys }} --}}
                    
                    <!-- Form -->
                    <form method="POST" action="{{ route(auth()->user()->role.'.cli.insert') }}">
                        @csrf
                        <input type="text" name="backto" value="{{ $redirectedTo }}" class="hidden">
                        {{-- {{ count($collectionKeys) }} --}}
                        @if (count($collectionKeys) <= 0)
                            <div 
                                id="header_title_modal"
                                class="py-3 flex items-center text-xs text-slate-800 tracking-wider font-bold uppercase before:flex-1 before:border-t before:border-gray-200 before:me-6 after:flex-1 after:border-t after:border-gray-200 after:ms-6 dark:text-neutral-500 dark:before:border-neutral-800 dark:after:border-neutral-800">
                                There is no available manual task
                            </div>
                        @endif
                        @foreach ($collectionKeys as $item)
                            {{-- <p>Affiliate Username: {{ $item['aff_username'] }}</p> --}}
                            <div 
                                id="header_title_modal"
                                class="py-3 flex items-center text-xs text-slate-800 tracking-wider font-bold uppercase before:flex-1 before:border-t before:border-gray-200 before:me-6 after:flex-1 after:border-t after:border-gray-200 after:ms-6 dark:text-neutral-500 dark:before:border-neutral-800 dark:after:border-neutral-800">
                                <span>{{ $item['aff_username'] }}</span>
                            </div>
                            <div class="bg-blue-500 p-1 text-white flex gap-1 justify-between">
                                {{-- <span>{{ $item['platform'] }}</span> --}}
                                <a class="flex gap-1 items-center text-sm hover:cursor-pointer hover:scale-105 transform transition-transform duration-300">
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"  viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 0c4.6 0 9.2 1 13.4 2.9L457.7 82.8c22 9.3 38.4 31 38.3 57.2c-.5 99.2-41.3 280.7-213.6 363.2c-16.7 8-36.1 8-52.8 0C57.3 420.7 16.5 239.2 16 140c-.1-26.2 16.3-47.9 38.3-57.2L242.7 2.9C246.8 1 251.4 0 256 0zm0 66.8l0 378.1C394 378 431.1 230.1 432 141.4L256 66.8s0 0 0 0z"/></svg>
                                    
                                    <span class="">
                                        {{ $item['platform'] }}
                                    </span>
                                </a>

                                <span>{{ $item['currency'] }}</span>
                            </div>
                            <div class="flex flex-col gap-2 p-2 bg-slate-100 mb-2">
                                @foreach ($item['campaign_id'] as $i)
                                    <div class="flex justify-between items-center gap-2">
                                        <input type="text" value="{{ $item['aff_username'] }}" name="aff_username[]" class="hidden">
                                        <!-- Form Group -->
                                        <div class="">
                                            <div class="relative">
                                                <label for="camp_id_input" class="text-slate-600">Campaing ID:</label>
                                                <input type="number" name="camp_id_input[]"
                                                    class="py-3 px-4 block w-full text-slate-600 border-gray-200 rounded-lg text-sm focus:border-none focus:ring-none disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-800 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600"
                                                    step="any" aria-describedby="cost_input-error" value="{{ $i }}" readonly>
                                                <div class="hidden absolute inset-y-0 end-0 pointer-events-none pe-3">
                                                    <svg class="size-5 text-red-500" width="16" height="16"
                                                        fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                                        <path
                                                            d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z" />
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- End Form Group -->
                                        <!-- Form Group -->
                                        <div class="">
                                            <div class="relative">
                                                <label for="cost_input" class="text-slate-600">Cost:</label>
                                                <input type="number" name="cost_input[]"
                                                    class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-800 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600"
                                                    step="any" aria-describedby="cost_input-error" required value="0">
                                                <div class="hidden absolute inset-y-0 end-0 pointer-events-none pe-3">
                                                    <svg class="size-5 text-red-500" width="16" height="16"
                                                        fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                                        <path
                                                            d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z" />
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- End Form Group -->
                                        <!-- Form Group -->
                                        <div class="">
                                            <div class="relative">
                                                <label for="impression_input" class="text-slate-600">Impression:</label>
                                                <input type="number" name="impression_input[]"
                                                    class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-800 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600"
                                                    step="any" required aria-describedby="impression_input-error" required value="0">
                                                <div class="hidden absolute inset-y-0 end-0 pointer-events-none pe-3">
                                                    <svg class="size-5 text-red-500" width="16" height="16"
                                                        fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                                        <path
                                                            d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z" />
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="">
                                            <div class="relative">
                                                <label for="click_input" class="text-slate-600">Clicks:</label>
                                                <input type="number" name="click_input[]"
                                                    class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-800 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600"
                                                    step="any" required aria-describedby="click_input-error" value="0">
                                                <div class="hidden absolute inset-y-0 end-0 pointer-events-none pe-3">
                                                    <svg class="size-5 text-red-500" width="16" height="16"
                                                        fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                                        <path
                                                            d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z" />
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- End Form Group -->
                                    </div> 
                                @endforeach
                            </div>
                        @endforeach
                        
                        <div class="flex justify-between items-center gap-2">
                            
                            <button type="submit"
                                class="w-full mt-2 py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-red-500 text-white hover:bg-red-700 focus:outline-none focus:bg-red-700 disabled:opacity-50 disabled:pointer-events-none"
                                data-hs-overlay="#hs-modal-currency">
                                Cancel</button>
                            <button type="submit"
                                class="w-full mt-2 py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">Save
                                Information</button>
                        </div>
                    </form>
                    <!-- End Form -->
                </div>
            </div>
        </div>
    </div>
</div>
