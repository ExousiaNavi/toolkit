<div class="absolute cube-wrapper w-full h-screen flex flex-col justify-center items-center z-[999]">
    <!-- Backdrop -->
    <div class="backdrop w-full h-[1080px] absolute top-0 left-0 bg-black opacity-50 z-[998]"></div>

    <!-- Loader -->
    <div class="cube-folding relative z-[9999]"> <!-- Keep loader in front of backdrop -->
        <span class="leaf1"><p class="p p1 -rotate-45">B</p></span>
        <span class="leaf2"><p class="p p2" style="transform: rotate(220deg) !important; color:rgb(50, 182, 50);">A</p></span>
        <span class="leaf3"><p class="p p3" style="transform: rotate(220deg) !important; color:rgb(50, 182, 50);">I</p></span>
        <span class="leaf4"><p class="p p4" style="transform: rotate(130deg) !important; ">J</p></span>
    </div>
   <div class="flex flex-col">
        <span class="loading relative z-[9999] title" data-name="Loading"></span>
        <span class="loading relative z-[9999] ellapse" data-name="Loading">Please Wait, Loading...</span>
   </div>
</div>
