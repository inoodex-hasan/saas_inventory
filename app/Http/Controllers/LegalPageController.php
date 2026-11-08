<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LegalPageController extends Controller
{
    /**
     * Display the dynamic Terms and Conditions page.
     */
    public function terms()
    {
        $raw = [];
        try {
            $raw = SiteSetting::getAll();
        } catch (\Throwable $e) {}

        $brandName      = $raw['brand_name'] ?? 'InoManage';
        $supportEmail   = $raw['support_email'] ?? 'support@inomanage.com';
        $contactPhone   = $raw['contact_phone'] ?? '';
        $contactAddress = $raw['contact_address'] ?? 'Dhaka, Bangladesh';

        $terms = $raw['terms_and_conditions'] ?? null;

        if (empty(trim($terms ?? ''))) {
            $terms = <<<MARKDOWN
# Terms and Conditions

**Last Updated:** November 2026

Welcome to **{$brandName}** ("the Service", "the Platform", "we", "us", or "our"). These Terms and Conditions govern your access to and use of {$brandName}, our cloud multi-tenant inventory, point-of-sale (POS), double-entry accounting, workshop services, and enterprise supply chain management software.

By registering for an account, accessing, or using {$brandName}, you agree to be legally bound by these Terms. If you are entering into these Terms on behalf of a company, organization, or other legal entity, you represent and warrant that you have full legal authority to bind that entity.

---

## 1. Multi-Tenant Architecture & Account Provisioning

- **Workspace Creation**: Upon registration, an isolated tenant workspace ("Organization" or "Team") is provisioned under your account.
- **Tenant Isolation**: We implement rigorous query-level tenant isolation (`BelongsToTenant`) and row-locking concurrency controls. Your organization's database records, inventory counts, financial ledgers, customer records, and transaction histories are strictly segregated from other tenants.
- **Account Responsibilities**: You are responsible for maintaining the confidentiality of your login credentials and for all activities that occur under your tenant workspace, including actions taken by invited team members and counter cashiers.

---

## 2. Subscription Plans, Billing & Payment Processing

- **Free Trial**: New organizations may be granted a complimentary 14-day trial period with access to core platform features. Upon expiration, an active subscription is required to maintain unrestricted workspace functionality.
- **Subscription Fees**: Subscriptions are billed in advance on a recurring monthly or annual basis as selected during checkout.
- **Bangladeshi & Global Payment Gateways**: We support automated credit/debit card billing via SSLCommerz, as well as manual mobile financial services (bKash, Nagad, Rocket) and direct Bank Transfers.
- **Manual Payment Verification (TrxID)**: If you submit a manual payment Transaction ID (TrxID), platform operators verify the ledger against banking statements before quota activation. False or fraudulent TrxIDs may result in immediate workspace termination.
- **Refund Policy**: Unless mandated by applicable consumer protection laws in Bangladesh, subscription fees are non-refundable once an activation cycle has commenced.

---

## 3. Data Ownership & Security

- **Your Data**: You retain 100% full legal ownership of all data, inventory catalogs, bills of materials, customer records, vendor invoices, journal entries, and repair logs imported into or generated within your workspace ("Customer Data").
- **Platform Rights**: You grant us only the limited license necessary to host, store, back up, and process Customer Data solely for providing the Service to you.
- **Automated Backups**: {$brandName} performs automated daily encrypted database snapshots. You can also export inventory valuations, balance sheets, and transaction logs in PDF, CSV, or Excel format at any time.

---

## 4. Pessimistic Concurrency & Service Level Agreement (SLA)

- **Inventory Accuracy Guarantee**: {$brandName} employs pessimistic row-locking (`lockForUpdate()`) to serialize simultaneous inventory dispatches. We guarantee zero negative inventory drift and protection against race conditions during peak checkout volumes.
- **Uptime Commitment**: We strive to deliver a **99.99% system availability SLA** for our multi-warehouse cloud infrastructure, excluding planned maintenance announced at least 24 hours in advance.

---

## 5. Acceptable Use Policy

You agree not to use {$brandName} to:
- Store, distribute, or transact illegal goods, pirated intellectual property, or prohibited materials under the laws of Bangladesh.
- Reverse-engineer, decompile, or attempt to extract the source code or proprietary concurrency algorithms of the platform.
- Probe, scan, or test the vulnerability of our multi-tenant isolation layers or attempt to access other tenants' data.
- Introduce malicious software, viruses, or automated scripts intended to disrupt platform stability.

---

## 6. Suspension and Termination

- **Past Due Subscriptions**: If a subscription renewal payment fails or expires, a grace period of up to 7 days is provided before your workspace is switched to read-only mode or temporary suspension.
- **Data Retention Post-Termination**: Upon termination or non-renewal, your Customer Data will be retained in encrypted cold storage for a minimum of 30 days, allowing you to request a full export before permanent deletion.

---

## 7. Limitation of Liability

To the maximum extent permitted by applicable law, {$brandName} and its operators shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including loss of profits, revenue, or business opportunities arising out of or related to your use of or inability to use the Service.

---

## 8. Governing Law & Jurisdiction

These Terms and Conditions shall be governed by and construed in accordance with the laws of the People's Republic of Bangladesh. Any dispute arising under or in connection with these Terms shall be subject to the exclusive jurisdiction of the competent courts in Dhaka, Bangladesh.

---

## 9. Contact Us

If you have any questions or concerns regarding these Terms and Conditions, please contact our legal and support team:

- **Email**: {$supportEmail}
- **Phone**: {$contactPhone}
- **Office Address**: {$contactAddress}
MARKDOWN;
        }

        $htmlContent = preg_match('/<\s*(p|h[1-6]|ul|ol|li|blockquote|div|span|hr|table|strong|em)\b/i', $terms)
            ? $terms
            : Str::markdown($terms);

        return view('terms', [
            'terms'     => $htmlContent,
            'brandName' => $brandName,
        ]);
    }

    /**
     * Display the dynamic Privacy Policy page.
     */
    public function privacy()
    {
        $raw = [];
        try {
            $raw = SiteSetting::getAll();
        } catch (\Throwable $e) {}

        $brandName      = $raw['brand_name'] ?? 'InoManage';
        $supportEmail   = $raw['support_email'] ?? 'support@inomanage.com';
        $contactPhone   = $raw['contact_phone'] ?? '';
        $contactAddress = $raw['contact_address'] ?? 'Dhaka, Bangladesh';

        $policy = $raw['privacy_policy'] ?? null;

        if (empty(trim($policy ?? ''))) {
            $policy = <<<MARKDOWN
# Privacy Policy

**Last Updated:** November 2026

At **{$brandName}** ("we", "our", or "us"), we are deeply committed to protecting your privacy and ensuring the confidentiality of your business and personal data. This Privacy Policy outlines our practices regarding the collection, storage, processing, and protection of information when you access or use {$brandName}.

---

## 1. Information We Collect

### A. Account & Organization Data
When you register for an account or create a tenant workspace, we collect:
- Account administrator name, business email address, and encrypted passwords.
- Company details, including business name, trade license numbers, tax registration IDs, corporate phone numbers, and physical warehouse/office addresses.

### B. Workspace Operational Data (Customer Data)
As you operate {$brandName}, our system stores:
- Product catalogs, SKU identifiers, serial numbers, cost prices, and retail rates.
- Point-of-Sale (POS) order tickets, sales quotations, and delivery challans.
- Vendor purchase orders, receiving dock slips, and supplier due ledgers.
- Financial transactions, Chart of Accounts (COA), journal entries, Trial Balances, and Profit & Loss reports.
- Workshop service tickets, device serials, diagnosis notes, and spare parts consumption logs.
- Staff profiles, department taxonomies, attendance records, and payroll salary slips.

### C. Technical & Audit Logs
- IP addresses, browser types, operating system identifiers, and microsecond timestamps recorded for immutable stock movement audits and multi-tenant security verification.

---

## 2. Multi-Tenant Data Isolation & Security

- **Strict Eloquent Scope**: Every query executed within {$brandName} is scoped to your specific `team_id` via our global tenant isolation architecture (`BelongsToTenant`). No tenant can access, view, or query another tenant's records.
- **Pessimistic Concurrency Logging**: Stock transactions create permanent immutable audit records containing user IDs and before/after counts, preventing tampering or unauthorized modification.
- **Data Encryption**: All data transmitted between your browser and our servers is encrypted using 256-bit Transport Layer Security (TLS 1.3 / SSL). Passwords are cryptographically hashed using standard Bcrypt / Argon2 algorithms.
- **Database Snapshots**: We conduct automated daily encrypted database backups stored in geographically distributed secure cloud repositories.

---

## 3. How We Use Your Information

We process your information exclusively to:
- Provision, authenticate, and maintain your tenant organization and user accounts.
- Execute real-time multi-warehouse stock reservation, inventory transfers, and POS checkouts.
- Automatically generate balanced double-entry accounting ledgers and financial statements.
- Compile vector-quality PDF sales invoices, purchase orders, money receipts, and payslips.
- Verify manual offline payment transactions (bKash, Nagad, Bank Deposit TrxIDs).
- Provide responsive technical support and diagnose system performance anomalies.

---

## 4. Zero Data Selling & Third-Party Disclosures

**We do NOT sell, rent, monetize, or trade your business data or customer lists to any third parties or advertisers.**

Information is only shared under the following limited circumstances:
- **Payment Processors**: Necessary transaction identifiers transmitted securely to licensed payment gateways (such as SSLCommerz) to process card charges.
- **Legal Compliance**: When strictly required by law, subpoena, or judicial order issued by competent law enforcement authorities in Bangladesh.

---

## 5. Data Retention & Your Rights

- **Data Ownership**: You retain unconditional ownership of all Customer Data stored within {$brandName}.
- **Exporting Data**: You have the right to download and export your customers, inventory ledgers, balance sheets, and sales reports at any time.
- **Tenant Erasure**: If you decide to cancel your subscription, you may request permanent deletion of your organization workspace and all associated database records. Following a 30-day safety cooling period, all database records and backup archives are permanently purged.

---

## 6. Cookies and Local Storage

{$brandName} uses essential cookies and local storage exclusively for:
- Maintaining secure, authenticated login sessions (`XSRF-TOKEN`, `laravel_session`).
- Preserving UI theme preferences and navigation state.
We do not use invasive third-party cross-site advertising or tracking cookies.

---

## 7. Updates to This Privacy Policy

We may periodically revise this Privacy Policy to reflect system enhancements, legislative changes, or improved security measures. If material changes are made, notice will be posted on your dashboard or delivered via your registered administrative email address.

---

## 8. Privacy Contact & Inquiries

For privacy-related inquiries, data export requests, or security audit questions, please contact our Data Protection Officer:

- **Email**: {$supportEmail}
- **Phone**: {$contactPhone}
- **Headquarters**: {$contactAddress}
MARKDOWN;
        }

        $htmlContent = preg_match('/<\s*(p|h[1-6]|ul|ol|li|blockquote|div|span|hr|table|strong|em)\b/i', $policy)
            ? $policy
            : Str::markdown($policy);

        return view('policy', [
            'policy'    => $htmlContent,
            'brandName' => $brandName,
        ]);
    }
}

