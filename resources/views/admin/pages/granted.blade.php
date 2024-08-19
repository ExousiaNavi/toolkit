<x-app-layout>
    <!-- Content -->
    <div class="w-full lg:ps-44 mt-2">
        <div class="bg-white border w-full">
            {{-- {{ $users }} --}}
            @include('admin.pages.tables.g_table', ['users'=>$users])
        </div>
    </div>

    @section('scripts')
        <script>
            $(document).ready(function(){
                console.log('connected...')
                let status = @json(session('status'));
                let title = @json(session('title'));
                let text = @json(session('text'));
                let icon = @json(session('icon'));

                //remove access
                $('.remove_access').click(function(){
                    // alert($(this).data('id'))
                    let id = $(this).data('id')
                    
                    $(`#decline-account-form-${id}`).submit()
                })

                // view all (Temporary disbaled)
                $('#grant_view_all').click(function(){
                   popup('info','Temporarily Disabled!','This action is not available right now!','info')
                })
                // add user (Temporary disbaled)
                $('#grant_add_user').click(function(){
                   popup('info','Temporarily Disabled!','This action is not available right now!','info')
                })
               
                // searh on table by ip
                $('#granted_search').on('input', function() {
                    var searchQuery = $(this).val().toLowerCase();

                    $('#granted_table tbody tr').filter(function() {
                        $(this).toggle($(this).text().toLowerCase().indexOf(searchQuery) > -1);
                    });
                });

                const popup = (status,title, text, icon) => {
                    if(status !== null){
                        Swal.fire({
                            title: title,
                            text: text,
                            icon: icon
                        });
                    }
                    
                }

                popup(status, title, text, icon)
            })
        </script>
    @endsection
</x-app-layout>