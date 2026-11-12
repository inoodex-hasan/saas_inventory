@php
    $testimonials = !empty($siteSettings['testimonials']) ? $siteSettings['testimonials'] : [
        [
            'name' => 'Tariqul Islam',
            'role' => 'VP of Supply Chain',
            'company' => 'Apex Logistics',
            'rating' => 5,
            'quote' => 'Before InoManage, stock audits across our 6 distribution hubs took three grueling shifts with constant variance. Now, real-time bin tracking and automated inter-warehouse transfers keep our accuracy steady at 99.98%.',
            'metric_icon' => '🚀',
            'metric_text' => '4.2x Faster Warehouse Fulfillment',
            'avatar_text' => 'TI',
        ],
        [
            'name' => 'Sarah Jenkins',
            'role' => 'Operations Director',
            'company' => 'UrbanTrend Stores',
            'rating' => 5,
            'quote' => 'Our retail stores process over 15,000 checkout tickets daily. The offline-resilient barcode POS and automated customer credit ledgers completely solved our overselling nightmare. Best ERP decision we ever made.',
            'metric_icon' => '⚡',
            'metric_text' => 'Zero Overselling Stockouts',
            'avatar_text' => 'SJ',
        ],
        [
            'name' => 'Mahmud Rahman, FCA',
            'role' => 'Chief Financial Officer',
            'company' => 'Prime Horizon',
            'rating' => 5,
            'quote' => 'Automated double-entry journals and instant bank reconciliation reduced our month-end financial closing cycle from 12 days to less than 4 hours. It provides audit-ready IFRS compliance straight out of the box.',
            'metric_icon' => '📈',
            'metric_text' => '80% Faster Month-End Close',
            'avatar_text' => 'MR',
        ],
    ];

    $avatarClasses = ['avatar-emerald', 'avatar-navy', 'avatar-gold'];
    $useCarousel = !empty($carousel);
    $chunks = $useCarousel ? collect($testimonials)->chunk(3) : collect([collect($testimonials)]);
@endphp

@if ($useCarousel && $chunks->count() > 1)
    <div id="reviewsCarousel" class="carousel slide mb-5" data-bs-ride="carousel" data-bs-interval="4500" data-bs-pause="hover">
        <div class="carousel-inner">
            @foreach ($chunks as $slideIndex => $chunk)
                <div class="carousel-item {{ $slideIndex === 0 ? 'active' : '' }}">
                    <div class="reviews-cards-grid">
                        @foreach ($chunk->values() as $index => $item)
                            @php
                                $rating = max(1, min(5, (int) ($item['rating'] ?? 5)));
                                $stars = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
                                $name = $item['name'] ?? 'Customer';
                                $initials = !empty($item['avatar_text'])
                                    ? $item['avatar_text']
                                    : collect(explode(' ', trim($name)))->filter()->map(fn ($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('');
                                $roleLine = trim(($item['role'] ?? '') . (!empty($item['company']) ? ' • ' . $item['company'] : ''));
                            @endphp
                            <article class="review-card {{ $index % 3 === 1 ? 'review-card-highlight' : '' }}">
                                <div class="review-stars mb-3">{{ $stars }}</div>
                                <blockquote class="review-quote">&ldquo;{{ $item['quote'] ?? '' }}&rdquo;</blockquote>

                                @if (!empty($item['metric_text']))
                                    <div class="review-metric-pill mb-4">
                                        <span class="metric-icon">{{ $item['metric_icon'] ?? '★' }}</span>
                                        <span>{{ $item['metric_text'] }}</span>
                                    </div>
                                @endif

                                <div class="review-author d-flex align-items-center gap-3">
                                    <div class="author-avatar-wrap">
                                        <div class="author-avatar {{ $avatarClasses[$index % 3] }}">{{ $initials ?: 'IN' }}</div>
                                    </div>
                                    <div>
                                        <div class="author-name">{{ $name }}</div>
                                        @if ($roleLine !== '')
                                            <div class="author-role">{{ $roleLine }}</div>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex justify-content-center gap-2 mt-4">
            @foreach ($chunks as $slideIndex => $chunk)
                <button
                    type="button"
                    data-bs-target="#reviewsCarousel"
                    data-bs-slide-to="{{ $slideIndex }}"
                    class="{{ $slideIndex === 0 ? 'active' : '' }}"
                    aria-current="{{ $slideIndex === 0 ? 'true' : 'false' }}"
                    aria-label="Reviews slide {{ $slideIndex + 1 }}"
                    style="width:10px;height:10px;border-radius:999px;border:0;background:{{ $slideIndex === 0 ? '#059669' : '#cbd5e1' }};"
                ></button>
            @endforeach
        </div>
    </div>
@else
    <div class="reviews-cards-grid mb-5">
        @foreach ($testimonials as $index => $item)
            @php
                $rating = max(1, min(5, (int) ($item['rating'] ?? 5)));
                $stars = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
                $name = $item['name'] ?? 'Customer';
                $initials = !empty($item['avatar_text'])
                    ? $item['avatar_text']
                    : collect(explode(' ', trim($name)))->filter()->map(fn ($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('');
                $roleLine = trim(($item['role'] ?? '') . (!empty($item['company']) ? ' • ' . $item['company'] : ''));
            @endphp
            <article class="review-card {{ $index % 3 === 1 ? 'review-card-highlight' : '' }}">
                <div class="review-stars mb-3">{{ $stars }}</div>
                <blockquote class="review-quote">&ldquo;{{ $item['quote'] ?? '' }}&rdquo;</blockquote>

                @if (!empty($item['metric_text']))
                    <div class="review-metric-pill mb-4">
                        <span class="metric-icon">{{ $item['metric_icon'] ?? '★' }}</span>
                        <span>{{ $item['metric_text'] }}</span>
                    </div>
                @endif

                <div class="review-author d-flex align-items-center gap-3">
                    <div class="author-avatar-wrap">
                        <div class="author-avatar {{ $avatarClasses[$index % 3] }}">{{ $initials ?: 'IN' }}</div>
                    </div>
                    <div>
                        <div class="author-name">{{ $name }}</div>
                        @if ($roleLine !== '')
                            <div class="author-role">{{ $roleLine }}</div>
                        @endif
                    </div>
                </div>
            </article>
        @endforeach
    </div>
@endif
