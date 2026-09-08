<div>
@if (session('error') || session('message'))
    <div class="fixed bottom-4 right-4 z-50">
        @if (session('error'))
            <div 
                x-data="{ show: true }" 
                x-show="show"
                x-transition 
                class="mb-3 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded shadow-lg"
                role="alert"
            >
                <strong class="font-bold">Error:</strong>
                <span class="block sm:inline">{{ session('error') }}</span>
                <button 
                    type="button" 
                    class="absolute top-0 right-0 px-3 py-1 text-red-500 font-bold"
                    @click="show = false"
                >×</button>
            </div>
        @endif

        @if (session('message'))
            <div 
                x-data="{ show: true }" 
                x-show="show"
                x-transition 
                class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded shadow-lg"
                role="alert"
            >
                <strong class="font-bold">Success:</strong>
                <span class="block sm:inline">{{ session('message') }}</span>
                <button 
                    type="button" 
                    class="absolute top-0 right-0 px-3 py-1 text-green-500 font-bold"
                    @click="show = false"
                >×</button>
            </div>
        @endif
    </div>
@endif

</div>
