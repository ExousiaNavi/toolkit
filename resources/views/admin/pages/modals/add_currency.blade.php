<div class="text-center">
    <button id="add_currency" type="button"
        class="hidden py-3 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none"
        aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-modal-signin" data-hs-overlay="#hs-modal-currency">
        Open modal
    </button>
</div>

<div id="hs-modal-currency" class="hs-overlay hidden size-full fixed top-0 start-0 z-[80] overflow-x-hidden overflow-y-auto"
    role="dialog" tabindex="-1" aria-labelledby="hs-modal-signin-label">
    <div
        class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto">
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-neutral-900 dark:border-neutral-800">
            <div class="p-4 sm:p-7">
                <div class="text-center">
                    <h3 id="hs-modal-currency-label" class="block text-2xl font-bold text-gray-800 dark:text-neutral-200">
                        Currency Section
                    </h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-neutral-400">
                        Add a new currency here?
                    </p>
                </div>

                <div class="mt-5">


                    <div
                        class="py-3 flex items-center text-xs text-gray-400 uppercase before:flex-1 before:border-t before:border-gray-200 before:me-6 after:flex-1 after:border-t after:border-gray-200 after:ms-6 dark:text-neutral-500 dark:before:border-neutral-800 dark:after:border-neutral-800">
                        CURRENCY</div>

                    <!-- Form -->
                    <form method="POST" action="{{ route(Auth::user()->role . '.baji.create.currency') }}">
                        @csrf

                        <input type="text" value="baji" class="hidden" name="brand">
                        <div class="flex flex-col gap-2">
                            <div class="flex justify-between items-center gap-2">
                                <!-- Form Group -->
                                <div class="">
                                    <div class="relative">
                                        <label for="currency_name" class="text-slate-600">Currency Type:</label>
                                        <input type="text" id="currency_name" name="currency_name"
                                            class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-800 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600"
                                            required aria-describedby="currency_name-error">
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
                                <div class="flex-1">
                                    <div class="relative">
                                        <label for="spreadsheet_link" class="text-slate-600">Spreadsheet Link:</label>
                                        <input type="text" id="spreadsheet_link" name="spreadsheet_link"
                                            class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-800 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600"
                                            required aria-describedby="spreadsheet_link-error">
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

                            <div
                                class="py-3 flex items-center text-xs text-gray-400 uppercase before:flex-1 before:border-t before:border-gray-200 before:me-6 after:flex-1 after:border-t after:border-gray-200 after:ms-6 dark:text-neutral-500 dark:before:border-neutral-800 dark:after:border-neutral-800">
                                Account
                            </div>

                            <div class="border p-2 flex gap-2 justify-between items-center bg-slate-50 rounded-sm">
                                <!-- Form Group -->
                                <div class="flex-1">
                                    <div class="relative">
                                        <label for="email" class="text-slate-600">Email:</label>
                                        <input type="email" name="email"
                                            class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-800 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600"
                                            required aria-describedby="email-error">
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
                                <div class="flex-1">
                                    <div class="relative">
                                        <label for="password" class="text-slate-600">Password:</label>
                                        <input type="text" name="password"
                                            class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-800 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600"
                                            required aria-describedby="password-error">
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

                        </div>
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
