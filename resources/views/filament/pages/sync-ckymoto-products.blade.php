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

        {{-- Cron Komutu Gösterimi --}}
        <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-6">
            <h3 class="text-lg font-semibold mb-4">Cron Komutu</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Aşağıdaki komutu sunucunuzun crontab'ına ekleyerek otomatik senkronizasyon yapabilirsiniz. Komutu kopyalamak için üzerine tıklayın.
            </p>

            @php
                $cronExamples = $this->getCronExamples();
            @endphp

            <div class="space-y-4">
                @foreach($cronExamples as $key => $example)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ $example['label'] }}
                        </label>
                        <div class="flex items-center gap-2">
                            <code 
                                class="flex-1 px-4 py-3 bg-gray-900 dark:bg-gray-950 text-gray-300 text-sm rounded-lg font-mono cursor-pointer hover:bg-gray-800 dark:hover:bg-gray-900 transition-colors select-all"
                                onclick="copyToClipboard(this, '{{ $key }}')"
                                id="cron-{{ $key }}"
                                title="Kopyalamak için tıklayın"
                            >{{ $example['command'] }}</code>
                            <button
                                type="button"
                                onclick="copyToClipboard(document.getElementById('cron-{{ $key }}'), '{{ $key }}')"
                                class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg text-sm font-medium transition-colors"
                                id="btn-{{ $key }}"
                            >
                                <span class="copy-text">Kopyala</span>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1" id="status-{{ $key }}"></p>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                <p class="text-sm text-amber-800 dark:text-amber-300">
                    <strong>Not:</strong> Cron komutunu kullanmadan önce sunucunuzda PHP path'inin doğru olduğundan emin olun. 
                    Eğer <code class="bg-amber-100 dark:bg-amber-900 px-1 rounded">php</code> komutu çalışmıyorsa, 
                    <code class="bg-amber-100 dark:bg-amber-900 px-1 rounded">which php</code> komutu ile PHP path'ini bulup 
                    komutta değiştirin.
                </p>
            </div>
        </div>
    </div>

    <script>
        function copyToClipboard(element, id) {
            const text = element.textContent.trim();
            
            navigator.clipboard.writeText(text).then(function() {
                const statusEl = document.getElementById('status-' + id);
                const btnEl = document.getElementById('btn-' + id);
                
                if (statusEl) {
                    statusEl.textContent = '✓ Kopyalandı!';
                    statusEl.className = 'text-xs text-success-600 dark:text-success-400 mt-1';
                    
                    setTimeout(function() {
                        statusEl.textContent = '';
                        statusEl.className = 'text-xs text-gray-500 dark:text-gray-500 mt-1';
                    }, 2000);
                }
                
                if (btnEl) {
                    const copyText = btnEl.querySelector('.copy-text');
                    if (copyText) {
                        const originalText = copyText.textContent;
                        copyText.textContent = 'Kopyalandı!';
                        btnEl.classList.add('bg-success-500');
                        btnEl.classList.remove('bg-primary-500');
                        
                        setTimeout(function() {
                            copyText.textContent = originalText;
                            btnEl.classList.remove('bg-success-500');
                            btnEl.classList.add('bg-primary-500');
                        }, 2000);
                    }
                }
            }, function(err) {
                console.error('Kopyalama başarısız:', err);
                const statusEl = document.getElementById('status-' + id);
                if (statusEl) {
                    statusEl.textContent = 'Kopyalama başarısız!';
                    statusEl.className = 'text-xs text-danger-600 dark:text-danger-400 mt-1';
                }
            });
        }
    </script>
</x-filament-panels::page>

