<div class="space-y-4">
 <section class="py-8 bg-gray-50">
  <div class="max-w-6xl mx-auto px-4">

    {{-- Record Details --}}
    <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden border border-gray-200 p-6">
        <div class="mb-2">
            <h2 class="text-sm text-gray-500 uppercase tracking-wide">Origin Office</h2>
            <p class="text-xl font-semibold text-gray-800">{{ $record->origin ?? 'N/A' }}</p>
        </div>
        
        <div class="mb-2">
            <h2 class="text-sm text-gray-500 uppercase tracking-wide">Reference</h2>
            <p class="text-xl font-semibold text-blue-700">{{ $record->reference ?? 'N/A' }}</p>
        </div>

        <div>
            <h2 class="text-sm text-gray-500 uppercase tracking-wide">Subject</h2>
            <p class="text-lg text-gray-800">{{ $record->subject ?? 'N/A'}}</p>
        </div>

        @if ($showSendButton)
            <button type="button"
                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg transition"
                data-bs-toggle="modal" data-bs-target="#sendModal">
                Send
            </button>
        @endif
	</br>
	

        {{-- Print --}}
                <a href="{{ route('records-pdf', $record->id) }}" class="bg-gray-500 hover:bg-black text-white font-semibold px-4 py-2 rounded-lg transition float-end">Print</a>
         {{-- end print --}}
    </div>

    @foreach ($transactions as $transact)
        <!-- Timeline Start -->
        <div class="relative border-l-2 border-gray-500 pl-6 ">            

            <!-- Time Label -->
            <div class="mb-10">
                <div class="bg-gray-100 text-black text-sm font-semibold inline-block px-3 py-1 rounded shadow">
                <i class="fas fa-clock text-gray-500 mr-1"></i> 
<strong>Logged At:</strong> {{ $transact->created_at?->format('M d, Y h:i A') ?? 'N/A' }}
                </div>
            </div>
            
            <!-- Timeline Item -->
            <div class="mb-10 relative">
                <div class="absolute -left-6 top-1">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center shadow 
                        {{ $transact->date_recieved ? 'bg-green-700 text-white' : 'bg-yellow-500 text-black' }}">
                        <i class="fas fa-clock text-lg"></i>
                    </div>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                    <span class="text-gray-500 text-sm flex items-center mb-2">
                        {{-- <i class="fas fa-clock mr-1"></i> 12:05 --}}
                    </span>
                    <h3 class="text-lg font-semibold mb-2">
                        <a href="#" class="text-blue-600 hover:underline">Status:</a> {{ $transact->status }}
                    </h3>
                    <div class="text-gray-700 mb-2">
                        <a href="#" class="text-blue-600 hover:underline">Sender:</a>
                        {{ $transact->office }}
                    </div>
                    <div class="text-gray-700 mb-2">
                        <a href="#" class="text-blue-600 hover:underline">Destination:</a>
                        {{ $transact->destination }}
                    </div>
                    <div class="text-gray-700 mb-4">
                        <a href="#" class="text-blue-600 hover:underline">Remarks:</a>
                        {{ $transact->remarks }}
                    </div>
                    <div class="mt-6 flex gap-2 items-center text-sm">
                            @if ($transact->date_recieved)
                                <span class="text-gray-500">
                                    <a href="#" class="text-blue-600 hover:underline">Date recieved:</a>
                                    {{ \Carbon\Carbon::parse($transact->date_recieved)->format('F j, Y g:i A') }}
                                    <a href="#" class="text-blue-600 hover:underline">Recieved by:</a>
                                    {{ $transact->recieved_by }}
                                </span>
                            @else
                                <button wire:click="markAsReceived({{ $transact->id }})"
                                    class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition">
                                    Mark as Received
                                </button>
                            @endif

                            {{-- @if ($transact->date_recieved)
                                <span class="text-gray-500">
                                    <a href="#" class="text-blue-600 hover:underline">Date recieved:</a>
                                    {{ \Carbon\Carbon::parse($transact->date_recieved)->format('F j, Y g:i A') }}
                                    <a href="#" class="text-blue-600 hover:underline">Recieved by:</a>
                                    {{ $transact->recieved_by }}
                                </span>
                            @else
                                <span class="italic text-blue-600 text-xl font-bold">
                                Sending<span class="animate-pulse text-3xl ml-1">...</span>
                                </span>
                            @endif --}}

                    </div>
                </div>
            </div>

        </div>
        <!-- /Timeline End -->
    @endforeach

  </div>
</section>

    <!-- Send Modal -->
        <div wire:ignore.self class="modal fade" id="sendModal" tabindex="-1" aria-labelledby="sendModalLabel" aria-hidden="true">
            <div class="modal-dialog">

                <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sendModalLabel">Send Transaction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                 @if (session()->has('message'))
                    <div class="mb-4 flex items-center justify-between px-4 py-3 rounded-lg bg-green-100 border border-green-300 shadow text-green-800">
                        <span class="text-sm font-medium">
                            {{ session('message') }}
                        </span>
                        <button type="button" onclick="this.parentElement.remove()"
                            class="ml-4 text-green-700 hover:text-green-900 focus:outline-none">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 8.586L4.293 2.879a1 1 0 00-1.414 1.414L8.586 10l-5.707 5.707a1 1 0 101.414 1.414L10 11.414l5.707 5.707a1 1 0 001.414-1.414L11.414 10l5.707-5.707a1 1 0 00-1.414-1.414L10 8.586z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                @endif
                <div class="modal-body space-y-4">

                    <!-- Office -->
                    <div>
                        <label for="office" class="block text-sm font-medium text-gray-700 mb-1">Destination Office</label>
                        <select wire:model="office" id="office"
                            class="w-full border-gray-300 rounded-lg shadow-sm px-4 py-2">
                            <option value="">-- Select Office --</option>
                            @foreach ($officeOptions as $name)
                                <option value="{{ $name }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('office') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select wire:model="status" id="status"
                            class="w-full border-gray-300 rounded-lg shadow-sm px-4 py-2">
                            <option value="">-- Select Status --</option>
                            @foreach ($statusOptions as $name)
                                <option value="{{ $name }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('status') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <!-- Remarks -->
                    <div>
                        <label for="remarks" class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                        <textarea wire:model="remarks" rows="3"
                            class="w-full border-gray-300 rounded-lg shadow-sm px-4 py-2 resize-none"
                            placeholder="Enter remarks"></textarea>
                        @error('remarks') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </div>

                </div>
                <div class="modal-footer">
                    <button id="modalCloseBtn" type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    @if ($showSendButton)
                        <button type="button"
                            wire:click="sendTransaction"
                            class="btn btn-primary">
                            Send
                        </button>
                    @endif
                </div>
                </div>
            </div>
        </div>

        

</div>
