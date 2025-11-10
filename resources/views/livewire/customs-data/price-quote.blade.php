<div>
    <div class="flex md:flex-row flex-col justify-between my-4" x-data="quoteSelect" x-init="initSelect()">
        <!-- Nút lưu / in / reset -->
        <div class="flex items-center gap-4">
            <x-filament::button icon="heroicon-o-document-arrow-down" color="success" outlined
                x-on:click="$store.quote.saveQuote()">
                Lưu
            </x-filament::button>

            {{ $this->printAction }}
            {{ $this->resetFormAction }}
        </div>

        <!-- Select báo giá -->
        <div class="flex md:justify-end items-center gap-4">
            <x-filament::input.wrapper wire:ignore>
                <x-filament::input.select x-ref="select" x-model="selectedKey" x-on:change="handleSelectChange">
                    <option value="">-- Báo giá đã lưu --</option>
                </x-filament::input.select>
            </x-filament::input.wrapper>

            <x-filament::button icon="heroicon-o-trash" color="danger" outlined x-bind:disabled="!selectedKey"
                x-on:click="deleteQuote">
                Xoá
            </x-filament::button>
        </div>
    </div>

    {{ $this->form }}

    <div class="flex justify-end items-center my-4 gap-4" x-data>
        {{-- TODO: Các nút khác --}}
    </div>
</div>

@script
    <script>
        // --- Livewire Events ---
        Livewire.on('print-price-pdf', (event) => window.open(event.url, '_blank'));

        // --- Alpine Store: Lưu & quản lý localStorage ---
        Alpine.store('quote', {
            MAX_QUOTES: 10,
            STORAGE_KEY: 'local-price-quote',

            saveQuote() {
                const data = JSON.parse(JSON.stringify($wire.$get('data')));
                if (!data) return new FilamentNotification()
                    .title('Không tìm thấy dữ liệu.')
                    .warning()
                    .send();

                const key = data?.quote_no?.trim();
                if (!key) return new FilamentNotification()
                    .title('Vui lòng nhập Mã báo giá.')
                    .warning()
                    .send();

                // Chuẩn hoá số (xóa dấu . ngăn cách nghìn, đổi , thành .)
                if (data.products && typeof data.products === 'object') {
                    Object.values(data.products).forEach(p => {
                        ['qty', 'unit_price'].forEach(field => {
                            if (p[field]) {
                                p[field] = p[field].toString()
                                    .replace(/\./g, '')
                                    .replace(/,/g, '.');
                            }
                        });
                    });
                }

                let allQuotes = JSON.parse(localStorage.getItem(this.STORAGE_KEY) || '{}');
                const keys = Object.keys(allQuotes);

                // Giới hạn 10 báo giá
                if (keys.length >= this.MAX_QUOTES && !allQuotes[key]) {
                    new FilamentNotification()
                        .title('Đã đạt giới hạn lưu trữ!')
                        .body(`Tối đa lưu: ${this.MAX_QUOTES} báo giá. Vui lòng xoá bớt trước khi lưu báo giá mới.`)
                        .warning()
                        .send();
                    return;
                }

                allQuotes[key] = data;
                localStorage.setItem(this.STORAGE_KEY, JSON.stringify(allQuotes));
                localStorage.setItem('selected-quote', key); // nhớ báo giá đang chọn

                new FilamentNotification()
                    .title(`Đã lưu báo giá: ${key}`)
                    .success()
                    .send();

                // Bắn event báo có cập nhật
                document.dispatchEvent(new CustomEvent('quotes-updated', {
                    detail: {
                        key
                    }
                }));
            },
        });

        // --- Alpine Component: Dropdown + tải / xoá ---
        Alpine.data('quoteSelect', () => ({
            quotes: {},
            selectedKey: '',

            initSelect() {
                this.refreshQuotes();

                // Không tự động load báo giá khi mới vào trang
                const lastKey = localStorage.getItem('selected-quote');
                if (lastKey && this.quotes[lastKey]) {
                    // chỉ hiển thị selectedKey chứ KHÔNG load quote vào Livewire
                    this.selectedKey = lastKey;
                }

                // Lắng nghe khi có quote mới lưu
                document.addEventListener('quotes-updated', (e) => {
                    this.refreshQuotes();

                    if (e.detail?.key) {
                        this.$nextTick(() => {
                            this.selectedKey = e.detail.key;
                            localStorage.setItem('selected-quote', this.selectedKey);
                            this.loadQuote(this.selectedKey);
                        });
                    }
                });
            },

            refreshQuotes() {
                const saved = localStorage.getItem(Alpine.store('quote').STORAGE_KEY);
                this.quotes = saved ? JSON.parse(saved) : {};

                const select = this.$refs.select;
                const current = this.selectedKey;

                // Clear các option cũ (giữ option đầu tiên)
                while (select.options.length > 1) select.remove(1);

                Object.keys(this.quotes).reverse().forEach(key => {
                    const opt = document.createElement('option');
                    opt.value = key;
                    opt.textContent = key;
                    select.appendChild(opt);
                });

                // Nếu không có selectedKey, ép chọn option rỗng
                if (!current || !this.quotes[current]) {
                    this.selectedKey = '';
                    this.$nextTick(() => {
                        select.value = '';
                    });
                } else {
                    this.$nextTick(() => {
                        select.value = current;
                    });
                }
            },

            handleSelectChange() {
                localStorage.setItem('selected-quote', this.selectedKey);
                this.loadQuote(this.selectedKey);
            },

            loadQuote(key) {
                if (!key) {
                    $wire.$set('data', {});
                    return;
                }

                const data = this.quotes[key];
                if (!data) return new FilamentNotification()
                    .title('Không tìm thấy báo giá.')
                    .warning()
                    .send();

                // Định dạng lại số để hiển thị đẹp
                if (data.products && typeof data.products === 'object') {
                    Object.values(data.products).forEach(p => {
                        ['qty', 'unit_price'].forEach(field => {
                            if (p[field]) p[field] = this.formatNumber(p[field]);
                        });
                    });
                }

                const current = $wire.$get('data.quote_no');
                $wire.$set('data', data);

                if (data.quote_no !== current) new FilamentNotification()
                    .title(`Đã tải báo giá: ${key}`)
                    .success()
                    .send();
            },

            deleteQuote() {
                const key = this.selectedKey;
                if (!key) return;
                if (!confirm(`❌ Xoá báo giá "${key}" khỏi thiết bị?`)) return;

                delete this.quotes[key];
                localStorage.setItem(Alpine.store('quote').STORAGE_KEY, JSON.stringify(this.quotes));
                this.selectedKey = '';
                localStorage.removeItem('selected-quote');

                this.refreshQuotes();
                document.dispatchEvent(new CustomEvent('quotes-updated'));
                new FilamentNotification()
                    .title(`Đã xoá báo giá: ${key}`)
                    .success()
                    .send();
            },

            formatNumber(value) {
                if (value === null || value === undefined || value === '') return '';
                const num = parseFloat(value.toString().replace(/\./g, '').replace(/,/g, '.'));
                if (isNaN(num)) return value;
                return new Intl.NumberFormat('vi-VN', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 3,
                }).format(num);
            },
        }));
    </script>
@endscript
