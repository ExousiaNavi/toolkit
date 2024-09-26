<div class="text-center">
    <button type="button" class="insert_sp hidden py-3 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none" aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-modal-signin" data-hs-overlay="#hs-modal-signin">
      Open modal
    </button>
  </div>
  
  <div id="hs-modal-signin" class="hs-overlay hidden size-full fixed top-0 start-0 z-[80] overflow-x-hidden overflow-y-auto" role="dialog" tabindex="-1" aria-labelledby="hs-modal-signin-label">
    <div class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-3xl sm:w-full m-3 sm:mx-auto">
      <div class="relative bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-neutral-900 dark:border-neutral-800">
        <div class="absolute top-2 end-2">
            <button type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-none focus:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-700 dark:hover:bg-neutral-600 dark:text-neutral-400 dark:focus:bg-neutral-600" aria-label="Close" data-hs-overlay="#hs-modal-signin">
              <span class="sr-only">Close</span>
              <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
          </div>
        <div class="p-4 sm:p-7">
          <div class="text-center">
            <h3 id="hs-modal-signin-label" class="block text-2xl font-bold text-gray-800 dark:text-neutral-200">Spreedsheet Form</h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-neutral-400">
              Add a new Spreedsheet
            </p>
          </div>
  
          <div class="mt-5">
            <!-- Form baji bo account -->
            <form method="POST" action="{{ route('admin.manage.spreedsheet.add') }}">
                @csrf
                <input type="text" name="brand" value="{{ $brand }}" class="hidden">
              <div class="grid grid-cols-3 flex-wrap gap-2 gap-y-4 mb-2">
                <!-- Form Group -->
                <div class="flex-1">
                  <label for="platform" class="block text-sm mb-2 dark:text-white">Platform</label>
                  <div class="relative">
                    <!-- Select -->
                      <select data-hs-select='{
                        "placeholder": "Select option...",
                        "toggleTag": "<button type=\"button\" aria-expanded=\"false\"></button>",
                        "toggleClasses": "hs-select-disabled:pointer-events-none hs-select-disabled:opacity-50 relative py-3 ps-4 pe-9 flex gap-x-2 text-nowrap w-full cursor-pointer bg-white border border-gray-200 rounded-lg text-start text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-neutral-600",
                        "dropdownClasses": "mt-2 z-50 w-full max-h-72 p-1 space-y-0.5 bg-white border border-gray-200 rounded-lg overflow-hidden overflow-y-auto [&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-track]:bg-gray-100 [&::-webkit-scrollbar-thumb]:bg-gray-300 dark:[&::-webkit-scrollbar-track]:bg-neutral-700 dark:[&::-webkit-scrollbar-thumb]:bg-neutral-500 dark:bg-neutral-900 dark:border-neutral-700",
                        "optionClasses": "py-2 px-4 w-full text-sm text-gray-800 cursor-pointer hover:bg-gray-100 rounded-lg focus:outline-none focus:bg-gray-100 hs-select-disabled:pointer-events-none hs-select-disabled:opacity-50 dark:bg-neutral-900 dark:hover:bg-neutral-800 dark:text-neutral-200 dark:focus:bg-neutral-800",
                        "optionTemplate": "<div class=\"flex justify-between items-center w-full\"><span data-title></span><span class=\"hidden hs-selected:block\"><svg class=\"shrink-0 size-3.5 text-blue-600 dark:text-blue-500 \" xmlns=\"http:.w3.org/2000/svg\" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><polyline points=\"20 6 9 17 4 12\"/></svg></span></div>",
                        "extraMarkup": "<div class=\"absolute top-1/2 end-3 -translate-y-1/2\"><svg class=\"shrink-0 size-3.5 text-gray-500 dark:text-neutral-500 \" xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"m7 15 5 5 5-5\"/><path d=\"m7 9 5-5 5 5\"/></svg></div>"
                      }' class="hidden" name="platform" required>
                        <option value="">Choose</option>
                        <option value="Richads">Richads</option>
                        <option value="TrafficStars">TrafficStars</option>
                        <option value="Adcash">Adcash</option>
                        <option value="TrafficNomads">TrafficNomads</option>
                        <option value="Adsterra">Adsterra</option>
                        <option value="FlatAd">FlatAd</option>
                        <option value="ADxAD">ADxAD</option>
                        <option value="Exoclick">Exoclick</option>
                        <option value="PropellerAds">PropellerAds</option>
                        <option value="ClickAdu">ClickAdu</option>
                        <option value="HilltopAds">HilltopAds</option>
                        <option value="Trafficforce">Trafficforce</option>
                        <option value="AdMaven">AdMaven</option>
                        <option value="DaoAD">DaoAD</option>
                        <option value="Onclicka">Onclicka</option>
                        <option value="TrafficShop">TrafficShop</option>

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
                    <input type="text" name="keyword" class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-800 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600" required aria-describedby="password-error" required>
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
                      <option value="-1|all">all</option>
                      <option value="2|VND">VND</option>
                      <option value="8|BDT">BDT</option>
                      <option value="15|USD">USD</option>
                      <option value="15|KHR">KHR</option>
                      <option value="7|INR">INR</option>
                      <option value="17|PKR">PKR</option>
                      <option value="16|PHP">PHP</option>
                      <option value="5|KRW">KRW</option>
                      <option value="6|IDR">IDR</option>
                      <option value="24|NPR">NPR</option>
                      <option value="9|THB">THB</option>
                      <option value="25|CAD">CAD</option>
                      <option value="11|HKD">HKD</option>
                      <option value="1|MYR">MYR</option>
                      <option value="4|SGD">SGD</option>
                  </select>
                  <!-- End Select -->
                  </div>
                </div>
                <!-- End Form Group -->
                <!-- Form Group -->
                <div class="col-span-3">
                  <div class="flex justify-between items-center">
                    <label for="password" class="block text-sm mb-2 dark:text-white">Spreedsheet Link</label>
                  </div>
                  <div class="relative">
                    <input type="text" name="link" class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-800 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600" required aria-describedby="password-error" required>
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
              </div>
              
            </form>
            <!-- End Form -->
          </div>
        </div>
      </div>
    </div>
  </div>