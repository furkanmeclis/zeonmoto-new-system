<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Content (Form) --}}
        {{ $this->content }}

        {{-- Sync Status --}}
        @if($syncStatus)
            <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-6">
                <h3 class="text-lg font-semibold mb-4">Senkronizasyon Durumu</h3>
                
                @if($syncStatus === 'running')
                    <div class="flex items-center space-x-3">
                        <x-filament::loading-indicator class="h-5 w-5 text-primary-500" />
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Senkronizasyon devam ediyor...
                        </span>
                    </div>
                @elseif($syncStatus === 'queued')
                    <div class="flex items-center space-x-3">
                        <x-filament::icon
                            icon="heroicon-o-clock"
                            class="h-5 w-5 text-amber-500"
                        />
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Senkronizasyon kuyruğa eklendi. Arka planda çalışacak.
                        </span>
                    </div>
                @elseif($syncStatus === 'completed')
                    <div class="flex items-center space-x-3 mb-4">
                        <x-filament::icon
                            icon="heroicon-o-check-circle"
                            class="h-5 w-5 text-success-500"
                        />
                        <span class="text-sm font-medium text-success-600 dark:text-success-400">
                            Senkronizasyon başarıyla tamamlandı.
                        </span>
                    </div>
                    
                    @if(isset($syncResults['output']) && !empty($syncResults['output']))
                        <div class="mt-4">
                            <details class="mt-2">
                                <summary class="cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-primary-500">
                                    Detaylı Çıktıyı Göster
                                </summary>
                                <div class="mt-2 p-4 bg-gray-900 dark:bg-gray-950 rounded-lg overflow-x-auto">
                                    <pre class="text-xs text-gray-300 whitespace-pre-wrap font-mono">{{ $syncResults['output'] }}</pre>
                                </div>
                            </details>
                        </div>
                    @endif
                @elseif($syncStatus === 'failed')
                    <div class="flex items-center space-x-3 mb-4">
                        <x-filament::icon
                            icon="heroicon-o-x-circle"
                            class="h-5 w-5 text-danger-500"
                        />
                        <span class="text-sm font-medium text-danger-600 dark:text-danger-400">
                            Senkronizasyon başarısız oldu.
                        </span>
                    </div>
                    
                    @if(isset($syncResults['message']))
                        <div class="mt-2 p-4 bg-danger-50 dark:bg-danger-900/20 rounded-lg">
                            <p class="text-sm text-danger-700 dark:text-danger-300">
                                {{ $syncResults['message'] }}
                            </p>
                        </div>
                    @endif
                    
                    @if(isset($syncResults['output']) && !empty($syncResults['output']))
                        <div class="mt-4">
                            <details class="mt-2">
                                <summary class="cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-primary-500">
                                    Hata Detaylarını Göster
                                </summary>
                                <div class="mt-2 p-4 bg-gray-900 dark:bg-gray-950 rounded-lg overflow-x-auto">
                                    <pre class="text-xs text-gray-300 whitespace-pre-wrap font-mono">{{ $syncResults['output'] }}</pre>
                                </div>
                            </details>
                        </div>
                    @endif
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>

