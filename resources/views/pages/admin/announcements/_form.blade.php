@php
    $isEdit = $announcement->exists;
    $initialAudience = old('audience', $announcement->audience ?: \App\Models\Announcement::AUDIENCE_ALL);
    $initialType = old('type', $announcement->type ?: \App\Models\Announcement::TYPE_INFO);
    $oldUserIds = old('user_ids', $selectedUserIds);
    $initialSelectedUsers = is_array($oldUserIds) ? array_values(array_map('strval', $oldUserIds)) : [];
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div
        x-data="{
            type: @js($initialType),
            audience: @js($initialAudience),
            selected: @js($initialSelectedUsers),
            search: '',
            preview: null,
            users: @js($users),
            pickImage(event) {
                if (this.preview) {
                    URL.revokeObjectURL(this.preview);
                }

                const file = event.target.files?.[0] ?? null;
                this.preview = file ? URL.createObjectURL(file) : null;
            },
            filteredUsers() {
                const query = this.search.trim().toLowerCase();
                const matches = query === ''
                    ? this.users
                    : this.users.filter(user => user.name.toLowerCase().includes(query) || user.email.toLowerCase().includes(query));

                return matches.slice(0, query === '' ? 30 : 100);
            },
            userLabel(id) {
                const user = this.users.find(item => item.id === id);

                return user ? user.name : id;
            },
        }"
        class="space-y-6"
    >
        <x-wms.card>
            <div>
                <h3 class="text-sm font-semibold text-slate-900">Isi pemberitahuan</h3>
                <p class="mt-1 text-xs text-slate-500">Tulis informasi yang akan tampil di lonceng notifikasi pengguna.</p>
            </div>

            <div class="mt-5 space-y-4">
                <x-wms.field label="Judul" for="title">
                    <x-wms.input
                        id="title"
                        name="title"
                        required
                        maxlength="150"
                        value="{{ old('title', $announcement->title) }}"
                        placeholder="Contoh: Jadwal stock opname bulan ini"
                    />
                </x-wms.field>

                <x-wms.field label="Jenis pemberitahuan" for="type" hint="Pilih Banner untuk menampilkan gambar besar di halaman ESS pengguna.">
                    <x-wms.select id="type" name="type" required x-model="type">
                        @foreach ([
                            \App\Models\Announcement::TYPE_INFO => 'Informasi',
                            \App\Models\Announcement::TYPE_SUCCESS => 'Kabar baik',
                            \App\Models\Announcement::TYPE_WARNING => 'Peringatan',
                            \App\Models\Announcement::TYPE_DANGER => 'Penting',
                            \App\Models\Announcement::TYPE_BANNER => 'Banner (dengan gambar)',
                        ] as $value => $label)
                            <option value="{{ $value }}" @selected($initialType === $value)>{{ $label }}</option>
                        @endforeach
                    </x-wms.select>
                </x-wms.field>

                <div x-show="type === '{{ \App\Models\Announcement::TYPE_BANNER }}'" x-cloak>
                    <x-wms.field
                        label="Gambar banner"
                        for="image"
                        hint="Format JPG, PNG, atau WEBP. Ukuran maksimal 4 MB. Gambar ini tampil sebagai banner setelah user ESS login."
                    >
                        <div class="space-y-3">
                            <div x-show="preview" x-cloak class="overflow-hidden rounded-xl border border-blue-100 bg-slate-50">
                                <img :src="preview" alt="Pratinjau gambar banner" class="max-h-64 w-full object-cover">
                            </div>

                            @if ($announcement->imageUrl())
                                <div x-show="! preview" class="overflow-hidden rounded-xl border border-blue-100 bg-slate-50">
                                    <img src="{{ $announcement->imageUrl() }}" alt="Gambar banner saat ini: {{ $announcement->title }}" class="max-h-64 w-full object-cover">
                                </div>

                                <p x-show="! preview" class="text-xs text-slate-500">Gambar saat ini. Unggah gambar baru untuk menggantinya.</p>
                            @endif

                            <input
                                id="image"
                                name="image"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                @change="pickImage($event)"
                                class="block w-full cursor-pointer rounded-xl border border-slate-300 bg-white text-sm text-slate-500 shadow-sm transition file:mr-4 file:cursor-pointer file:border-0 file:bg-blue-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
                            >
                        </div>
                    </x-wms.field>
                </div>

                <x-wms.field label="Isi pemberitahuan" for="body">
                    <x-wms.textarea
                        id="body"
                        name="body"
                        rows="7"
                        maxlength="5000"
                        placeholder="Tulis detail pemberitahuan..."
                        x-bind:required="type !== '{{ \App\Models\Announcement::TYPE_BANNER }}'"
                    >{{ old('body', $announcement->body) }}</x-wms.textarea>

                    <p x-show="type === '{{ \App\Models\Announcement::TYPE_BANNER }}'" x-cloak class="text-xs text-slate-500">
                        Isi pemberitahuan opsional untuk banner. Gunakan jika perlu keterangan tambahan di bawah gambar.
                    </p>
                </x-wms.field>
            </div>
        </x-wms.card>

        <x-wms.card>
            <div>
                <h3 class="text-sm font-semibold text-slate-900">Penerima</h3>
                <p class="mt-1 text-xs text-slate-500">Pilih apakah pemberitahuan dikirim ke semua user atau user tertentu saja.</p>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                <label
                    class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition"
                    :class="audience === '{{ \App\Models\Announcement::AUDIENCE_ALL }}' ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-500' : 'border-slate-200 bg-white hover:border-blue-200'"
                >
                    <input
                        type="radio"
                        name="audience"
                        value="{{ \App\Models\Announcement::AUDIENCE_ALL }}"
                        x-model="audience"
                        class="mt-0.5 h-4 w-4 border-slate-300 text-blue-600 focus:ring-blue-500"
                    >

                    <span>
                        <span class="block text-sm font-semibold text-slate-900">Semua user</span>
                        <span class="mt-1 block text-xs leading-relaxed text-slate-500">Tampil untuk seluruh pengguna, termasuk user yang ditambahkan kemudian.</span>
                    </span>
                </label>

                <label
                    class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition"
                    :class="audience === '{{ \App\Models\Announcement::AUDIENCE_SELECTED }}' ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-500' : 'border-slate-200 bg-white hover:border-blue-200'"
                >
                    <input
                        type="radio"
                        name="audience"
                        value="{{ \App\Models\Announcement::AUDIENCE_SELECTED }}"
                        x-model="audience"
                        class="mt-0.5 h-4 w-4 border-slate-300 text-blue-600 focus:ring-blue-500"
                    >

                    <span>
                        <span class="block text-sm font-semibold text-slate-900">User tertentu</span>
                        <span class="mt-1 block text-xs leading-relaxed text-slate-500">Pemberitahuan pribadi yang hanya bisa dibaca oleh user yang dipilih.</span>
                    </span>
                </label>
            </div>

            <x-input-error :messages="$errors->get('audience')" class="mt-2" />

            <div x-show="audience === '{{ \App\Models\Announcement::AUDIENCE_SELECTED }}'" x-cloak class="mt-5 space-y-4">
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="user_ids[]" :value="id" :disabled="audience !== '{{ \App\Models\Announcement::AUDIENCE_SELECTED }}'">
                </template>

                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-sm font-medium text-slate-900">
                        <span x-text="selected.length"></span> user dipilih
                    </p>

                    <button
                        type="button"
                        x-show="selected.length > 0"
                        @click="selected = []"
                        class="text-xs font-semibold text-rose-600 transition hover:text-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500"
                    >
                        Hapus semua pilihan
                    </button>
                </div>

                <div x-show="selected.length > 0" class="flex flex-wrap gap-2">
                    <template x-for="id in selected" :key="id">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-100">
                            <span class="max-w-48 truncate" x-text="userLabel(id)"></span>
                            <button
                                type="button"
                                @click="selected = selected.filter(item => item !== id)"
                                class="text-blue-500 transition hover:text-rose-600 focus:outline-none"
                                :aria-label="'Hapus ' + userLabel(id)"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </span>
                    </template>
                </div>

                <x-wms.field label="Cari user" for="user-search">
                    <x-wms.input
                        id="user-search"
                        type="search"
                        x-model="search"
                        placeholder="Cari nama atau email..."
                        autocomplete="off"
                    />
                </x-wms.field>

                <p
                    class="text-xs text-slate-500"
                    x-text="search.trim() === '' ? 'Menampilkan 30 user pertama. Gunakan pencarian untuk menemukan user lain.' : filteredUsers().length + ' user ditemukan.'"
                ></p>

                <div class="max-h-80 overflow-y-auto rounded-xl border border-blue-100 bg-slate-50/50">
                    <template x-for="user in filteredUsers()" :key="user.id">
                        <label class="flex cursor-pointer items-center gap-3 border-b border-blue-50 px-3 py-2.5 transition last:border-b-0 hover:bg-white">
                            <input
                                type="checkbox"
                                :value="user.id"
                                x-model="selected"
                                class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                            >

                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium text-slate-900" x-text="user.name"></span>
                                <span class="block truncate text-xs text-slate-500" x-text="user.email"></span>
                            </span>

                            <span class="shrink-0 text-[10px] font-semibold uppercase tracking-wider text-slate-400" x-text="user.role === 'admin' ? 'Admin' : 'User'"></span>
                        </label>
                    </template>
                </div>

                <div x-show="filteredUsers().length === 0" class="rounded-xl border border-dashed border-blue-200 bg-blue-50/40 px-4 py-8 text-center text-sm text-slate-500">
                    User tidak ditemukan.
                </div>

                <x-input-error :messages="$errors->get('user_ids')" />
                <x-input-error :messages="collect($errors->get('user_ids.*'))->flatten()->all()" />
            </div>
        </x-wms.card>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <x-wms.button :href="route('admin.announcements.index')" variant="secondary">Batal</x-wms.button>

            <x-wms.button type="submit">
                {{ $isEdit ? 'Simpan perubahan' : 'Kirim pemberitahuan' }}
            </x-wms.button>
        </div>
    </div>
</form>
