@php
    $faqs = !empty($siteSettings['faqs']) ? $siteSettings['faqs'] : [
        [
            'question' => 'Can we import our existing inventory, suppliers, and customer records from Excel?',
            'answer' => 'Yes, absolutely. InoManage includes a guided 1-click CSV/Excel import wizard with automated column mapping for product catalogs, batch numbers, vendor details, and opening account balances. Our dedicated onboarding engineering team also provides complimentary white-glove migration from legacy ERPs (Tally, QuickBooks, SAP, Odoo) with zero data loss.',
        ],
        [
            'question' => 'How does multi-warehouse pessimistic row-locking prevent overselling?',
            'answer' => 'InoManage locks the required SKU row in your database at the millisecond a sales quotation is drafted or POS cart item is scanned. This reserves that quantity at your designated fulfillment hub, eliminating inventory race conditions even during high-velocity promotional flash sales.',
        ],
        [
            'question' => 'Does the POS continue processing sales when internet connectivity drops?',
            'answer' => 'Yes. The InoManage POS terminal operates with progressive local offline storage. Store cashiers can scan barcodes, issue printable cash receipts, and deduct physical shelf stock without disruption. The moment an internet connection is re-established, all offline transactions silently sync back to your cloud general ledger.',
        ],
        [
            'question' => 'Is our financial data secure and compliant with official auditing standards?',
            'answer' => 'Yes. Every ledger entry follows strict IFRS/GAAP double-entry bookkeeping principles with immutable audit trails. All databases are secured with 256-bit military-grade encryption at rest and TLS 1.3 in transit, backed by automated hourly geo-redundant backups.',
        ],
        [
            'question' => 'What happens when our 3-day free trial ends?',
            'answer' => 'You will never be billed automatically. At the end of the trial, you can select the subscription plan that aligns with your scale or export your full dataset with a single click. Your data remains protected and intact.',
        ],
    ];
@endphp

<div class="faq-accordion-wrap">
    @foreach ($faqs as $index => $faq)
        <div class="faq-accordion-item">
            <button class="faq-accordion-header d-flex align-items-center justify-content-between w-100" type="button" aria-expanded="{{ $index === 0 ? 'true' : 'false' }}">
                <span class="faq-q-text">{{ $faq['question'] ?? '' }}</span>
                <span class="faq-q-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </span>
            </button>
            <div class="faq-accordion-body {{ $index === 0 ? 'show' : '' }}">
                <p>{{ $faq['answer'] ?? '' }}</p>
            </div>
        </div>
    @endforeach
</div>

