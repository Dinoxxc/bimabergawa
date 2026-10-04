<div class="space-y-6">
    <!-- Comment Form -->
    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
        <h3 class="text-sm font-semibold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
            <svg class="w-4 h-4 text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
            Internal Collaboration & Notes
        </h3>
        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
            Share contextual operational updates or coordinate quotation preparation. Use @name to mention colleagues.
        </p>

        <form wire:submit="submitComment" class="mt-4 space-y-4">
            <div>
                <textarea
                    wire:model="commentText"
                    rows="3"
                    placeholder="Type note or coordination comment here..."
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder-zinc-500 dark:focus:border-zinc-100 dark:focus:ring-zinc-100"
                    required
                ></textarea>
                @error('commentText')
                    <span class="mt-1 text-xs text-rose-500">{{ $message }}</span>
                @enderror
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                <div class="flex items-center gap-4 text-xs">
                    @if(auth()->user()?->hasAnyRole(['super_admin', 'sales_pic', 'finance', 'logistics']))
                        <span class="font-medium text-zinc-600 dark:text-zinc-400">Visibility:</span>
                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" wire:model="type" value="internal" class="text-zinc-900 focus:ring-zinc-900 dark:text-zinc-100">
                            <span class="text-zinc-700 dark:text-zinc-300">Internal Staff Only</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" wire:model="type" value="agent_visible" class="text-zinc-900 focus:ring-zinc-900 dark:text-zinc-100">
                            <span class="text-zinc-700 dark:text-zinc-300">Visible to Freelance Agent</span>
                        </label>
                    @else
                        <span class="inline-flex items-center rounded-sm bg-blue-50 px-2 py-1 text-[11px] font-medium text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-900">
                            Communication with Assigned Sales PIC
                        </span>
                    @endif
                </div>

                <button
                    type="submit"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-zinc-900 px-4 py-2 text-xs font-semibold text-white transition hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200"
                >
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                    <span>Post Note</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Timeline List -->
    <div class="space-y-3">
        @forelse($comments as $c)
            <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-2xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-2 border-b border-zinc-100 pb-2.5 dark:border-zinc-800/80">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-zinc-100 text-xs font-bold text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200">
                            {{ substr($c->user->name ?? 'U', 0, 1) }}
                        </span>
                        <span class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $c->user->name ?? 'System' }}
                        </span>
                        <span class="text-[11px] text-zinc-500">
                            ({{ $c->user->roles->first()?->name ?? 'Staff' }})
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        @if($c->type === 'agent_visible')
                            <span class="inline-flex items-center rounded-sm bg-blue-50 px-1.5 py-0.5 text-[10px] font-medium text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-900">
                                Agent Visible
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-sm bg-zinc-100 px-1.5 py-0.5 text-[10px] font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                Internal Staff
                            </span>
                        @endif
                        <span class="text-[11px] text-zinc-400">
                            {{ $c->created_at->diffForHumans() }}
                        </span>
                    </div>
                </div>

                <div class="mt-2 text-xs leading-relaxed text-zinc-700 dark:text-zinc-300 whitespace-pre-line">
                    {{ $c->comment }}
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-dashed border-zinc-300 p-6 text-center text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                No coordination notes recorded yet for this inquiry.
            </div>
        @endforelse
    </div>
</div>
