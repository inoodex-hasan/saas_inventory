import { ref } from 'vue';

// Initialize language from localStorage (consistent with welcome page)
const storedLang = typeof window !== 'undefined'
    ? (localStorage.getItem('dashboard_lang') || localStorage.getItem('welcome_lang') || 'en')
    : 'en';

export const currentLang = ref(storedLang);

export function setLang(lang) {
    currentLang.value = lang;
    if (typeof window !== 'undefined') {
        localStorage.setItem('dashboard_lang', lang);
        localStorage.setItem('welcome_lang', lang);
    }
}

export function toggleLang() {
    setLang(currentLang.value === 'bn' ? 'en' : 'bn');
}

const bnDigits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

// Convert western digits to Bengali digits
export function toBnDigits(value) {
    return String(value).replace(/\d/g, (d) => bnDigits[d]);
}

export const dictionary = {
    // Top Bar & Global Layout
    'Workspace Overview': 'ওয়ার্কস্পেস ওভারভিউ',
    'Platform Control Panel': 'প্ল্যাটফর্ম কন্ট্রোল প্যানেল',
    'Super Admin Mode': 'সুপার অ্যাডমিন মোড',
    'Super Admin': 'সুপার অ্যাডমিন',
    'Inspecting Tenant': 'টেন্যান্ট পরিদর্শন',
    'Exit Workspace': 'ওয়ার্কস্পেস থেকে বের হন',
    'Exit Tenant Workspace': 'ওয়ার্কস্পেস থেকে বের হন',
    'Exit Impersonation': 'ইমপারসনেশন থেকে বের হন',
    'Current Organization': 'বর্তমান প্রতিষ্ঠান',
    'Switch Workspace': 'ওয়ার্কস্পেস পরিবর্তন',
    'Signed in as': 'লগইন করা আছে',
    'Profile Settings': 'প্রোফাইল সেটিংস',
    'Organization Settings': 'প্রতিষ্ঠান সেটিংস',
    'Team & Staff Roles': 'টিম ও স্টাফ পদবি',
    'Subscription & Billing': 'সাবস্ক্রিপশন ও বিলিং',
    'Log Out': 'লগআউট',
    'Notifications': 'নোটিফিকেশন',
    'Workspace Alerts': 'ওয়ার্কস্পেস অ্যালার্ট',
    'Manage Subscription & Billing': 'সাবস্ক্রিপশন ও বিলিং পরিচালনা',
    'Keep Subscription (Renew Now)': 'সাবস্ক্রিপশন চালু রাখুন (এখনই নবায়ন করুন)',
    'All caught up!': 'সব ঠিক আছে!',
    'Your subscription is in good standing. No urgent alerts.': 'আপনার সাবস্ক্রিপশন সচল রয়েছে। কোনো জরুরি সতর্কতা নেই।',
    'POS': 'পিওএস',
    'Open Point of Sale Terminal': 'পিওএস বিক্রয় টার্মিনাল খুলুন',
    'Collapse to icon sidebar (expand on hover)': 'সাইডবার ছোট করুন (হোভারে বড় হবে)',
    'Pin sidebar open': 'সাইডবার পিন করে রাখুন',
    'Unpin sidebar (Auto-collapse on hover)': 'সাইডবার আনপিন করুন',

    // Super Admin Sidebar Navigation
    'Platform Management': 'প্ল্যাটফর্ম ব্যবস্থাপনা',
    'Platform Overview & Tenants': 'প্ল্যাটফর্ম ওভারভিউ ও টেন্যান্ট',
    'Subscription Plans': 'সাবস্ক্রিপশন প্ল্যান',
    'Payment Verifications': 'পেমেন্ট ভেরিফিকেশন',
    'Site Settings & Branding': 'সাইট সেটিংস ও ব্র্যান্ডিং',
    'Security & Account': 'নিরাপত্তা ও অ্যাকাউন্ট',

    // Tenant Sidebar Navigation
    'Dashboard': 'ড্যাশবোর্ড',
    'Sales & CRM': 'বিক্রয় ও কাস্টমার',
    'POS Terminal': 'পিওএস টার্মিনাল',
    'Quotations & Estimates': 'কোটেশন ও এস্টিমেট',
    'Sales & Dispatches': 'বিক্রয় ও ডিসপ্যাচ',
    'Delivery Challans': 'ডেলিভারি চালান',
    'Customer Due Ledger': 'গ্রাহক বাকি লেজার',
    'Return Management': 'রিটার্ন ব্যবস্থাপনা',
    'Procurement & Vendors': 'ক্রয় ও সরবরাহকারী',
    'Purchases & Inbound': 'ক্রয় ও ইনবাউন্ড',
    'Vendors & Suppliers': 'সরবরাহকারী ও ভেন্ডর',
    'Vendor Due Ledger': 'ভেন্ডর বাকি লেজার',
    'Inventory & Warehouses': 'ইনভেন্টরি ও গুদাম',
    'Stock & Inventory': 'স্টক ও ইনভেন্টরি',
    'Products & Catalog': 'পণ্য ও ক্যাটালগ',
    'Categories': 'ক্যাটাগরি',
    'Brands': 'ব্র্যান্ড',
    'Warehouses & Hubs': 'ওয়্যারহাউস ও হাব',
    'Serials & Warranties': 'সিরিয়াল ও ওয়ারেন্টি',
    'Services & Projects': 'সার্ভিস ও প্রকল্প',
    'Services & Workshop': 'সার্ভিস ও ওয়ার্কশপ',
    'Projects & Costing': 'প্রজেক্ট ও খরচ হিসাব',
    'HR & Payroll': 'মানবসম্পদ ও বেতন',
    'Staff & Employees': 'কর্মকর্তা ও কর্মচারী',
    'Daily Expenses': 'দৈনন্দিন খরচ',
    'Staff Payroll': 'স্টাফ বেতন',
    'Accounting & Finance': 'হিসাব ও অর্থায়ন',
    'Bank & MFS Accounts': 'ব্যাংক ও মোবাইল ব্যাংকিং',
    'Chart of Accounts': 'চার্ট অব অ্যাকাউন্টস',
    'Journal Entries': 'জাবেদা এন্ট্রি (Journal Entry)',
    'Trial Balance': 'রেওয়ামিল (Trial Balance)',
    'Profit & Loss': 'লাভ-ক্ষতি হিসাব',
    'Balance Sheet': 'উদ্বৃত্তপত্র (Balance Sheet)',
    'General Ledger': 'খতিয়ান (General Ledger)',
    'Analytics': 'অ্যানালিটিক্স',
    'Reports & Valuation': 'রিপোর্ট ও মূল্যায়ন',
    'Settings & Team': 'সেটিংস ও টিম',
    'Company & Branding': 'প্রতিষ্ঠান ও ব্র্যান্ডিং',
    'Team & Roles': 'টিম ও ভূমিকা',

    // Super Admin Dashboard KPIs & Grid
    'Platform Super-Admin Control Plane': 'প্ল্যাটফর্ম সুপার-অ্যাডমিন কন্ট্রোল প্যানেল',
    'Tenant provisioning, lifecycle administration, cross-tenant isolation enforcement, and workspace switching': 'টেন্যান্ট ব্যবস্থাপনা, লাইফসাইকেল অ্যাডমিনিস্ট্রেশন ও ওয়ার্কস্পেস নিয়ন্ত্রণ',
    'Total Tenants': 'মোট টেন্যান্ট',
    'Active Tenants': 'সক্রিয় টেন্যান্ট',
    'Suspended': 'স্থগিত',
    'Platform Users': 'প্ল্যাটফর্ম ব্যবহারকারী',
    'Total Products': 'মোট পণ্য',
    'Total Orders': 'মোট অর্ডার',
    'Active Subscriptions': 'সক্রিয় সাবস্ক্রিপশন',
    'Pending Verifications': 'অপেক্ষমাণ ভেরিফিকেশন',
    'Monthly Recurring (MRR)': 'মাসিক পুনরাবৃত্তিক আয় (MRR)',
    'Provision New Tenant': 'নতুন টেন্যান্ট যুক্ত করুন',
    'Search tenants by name, domain, owner...': 'নাম, ডোমেইন বা মালিক দিয়ে খুঁজুন...',
    'All Plans': 'সকল প্ল্যান',
    'All Statuses': 'সকল অবস্থা',
    'Active Only': 'শুধুমাত্র সক্রিয়',
    'Suspended Only': 'শুধুমাত্র স্থগিত',
    'Clear': 'পরিষ্কার করুন',
    'New Tenant': 'নতুন টেন্যান্ট',
    'Individual SaaS Account': 'ব্যক্তিগত SaaS অ্যাকাউন্ট',
    'Tenant / Business': 'টেন্যান্ট / প্রতিষ্ঠান',
    'Plan & Billing': 'প্ল্যান ও বিলিং',
    'Created': 'তৈরির তারিখ',
    'Actions': 'অ্যাকশন',
    'Action': 'অ্যাকশন',
    'Inspect': 'পরিদর্শন',
    'Manage': 'পরিচালনা',
    'Switch': 'প্রবেশ করুন',
    'Active': 'সক্রিয়',
    'Pending': 'অপেক্ষমাণ',
    'Expired': 'মেয়াদোত্তীর্ণ',
    'Monthly': 'মাসিক',
    'Yearly': 'বার্ষিক',
    'Lifetime': 'লাইফটাইম',
    'Total Revenue': 'মোট আয়',
    'Owner:': 'মালিক:',
    'Email:': 'ইমেইল:',
    'Phone:': 'ফোন:',
    'Staff': 'স্টাফ',
    'Hubs': 'হাব / গুদাম',
    'SKUs': 'এসকেইউ',
    'Orders': 'অর্ডার',
    'Suspend': 'স্থগিত করুন',
    'Reactivate': 'সক্রিয় করুন',
    'left': 'বাকি',
    '{n} left': '{n} বাকি',

    // Tenant Dashboard
    'Admin Dashboard': 'অ্যাডমিন ড্যাশবোর্ড',
    'Real-time inventory valuation, trends, analytics & operations': 'রিয়েল-টাইম ইনভেন্টরি মূল্যায়ন, বিশ্লেষণ ও সামগ্রিক কার্যক্রম',
    'Quick Operations': 'দ্রুত কার্যক্রম',
    'New Sales Order': 'নতুন বিক্রয় অর্ডার',
    'New Purchase': 'নতুন ক্রয় অর্ডার',
    'Add Product': 'নতুন পণ্য যোগ',
    'Total Inventory Value': 'মোট ইনভেন্টরি স্টক মূল্য',
    'Across': 'মোট',
    'active catalog products': 'টি সক্রিয় ক্যাটালগ পণ্য',
    'Across {n} active catalog products': 'মোট {n}টি সক্রিয় ক্যাটালগ পণ্য',
    'Total Sales Dispatched': 'মোট বিক্রয় সম্পন্ন',
    'total confirmed client orders': 'টি নিশ্চিতকৃত গ্রাহক অর্ডার',
    '{n} total confirmed client orders': '{n}টি নিশ্চিতকৃত গ্রাহক অর্ডার',
    'Inbound Purchases': 'ক্রয় ইনবাউন্ড',
    'Open POs': 'টি খোলা পিও',
    '{n} Open POs': '{n}টি খোলা পিও',
    'Worth': 'মূল্য',
    'Worth {amount}': 'মূল্য {amount}',
    'Low Stock Threshold': 'স্বল্প স্টক সীমা',
    'Items Low': 'টি পণ্য স্বল্প স্টকে',
    '{n} Items Low': '{n}টি পণ্য স্বল্প স্টকে',
    'Needs purchase replenishment': 'পুনরায় ক্রয় প্রয়োজন',
    'Revenue & Procurement Trends': 'আয় ও ক্রয় প্রবণতা',
    'Monthly sales turnover vs. inventory procurement outlay': 'মাসিক বিক্রয় বনাম ক্রয় ব্যয় বিশ্লেষণ',
    'Sales Invoices': 'বিক্রয় ইনভয়েস',
    'Purchase POs': 'ক্রয় অর্ডার (PO)',
    'Inventory Holding by Category': 'ক্যাটাগরি অনুযায়ী ইনভেন্টরি স্টক',
    'Portfolio distribution and capital allocation': 'পোর্টফোলিও বিভাজন ও মূলধন বণ্টন',
    'View Full Catalog & Categories': 'সম্পূর্ণ ক্যাটালগ ও ক্যাটাগরি দেখুন',
    'Warehouse & Hub Holding Breakdown': 'ওয়্যারহাউস ও হাব অনুযায়ী স্টক বিভাজন',
    'Capital valuation and physical inventory across active fulfillment locations': 'সকল ওয়্যারহাউসের পণ্য ও মূলধন মূল্যায়ন',
    'Manage Hubs': 'ওয়্যারহাউস পরিচালনা',
    'Default': 'ডিফল্ট',
    'Units': 'একক',
    'Low Stock Radar': 'স্বল্প স্টক রাডার',
    'Products requiring purchase requisition': 'যেসব পণ্যের পুনরায় অর্ডার প্রয়োজন',
    'View Catalog': 'ক্যাটালগ দেখুন',
    'Product': 'পণ্য',
    'Available': 'মজুদ',
    'Reorder Lvl': 'পুনঃঅর্ডার মাত্রা',
    'Order PO': 'পিও অর্ডার',
    'No low stock items. All inventory levels optimal.': 'কোনো স্বল্প স্টক নেই। সকল পণ্যের মজুদ সন্তোষজনক।',
    'Recent Sales': 'সাম্প্রতিক বিক্রয়',
    'Recent Inbound': 'সাম্প্রতিক ক্রয়',
    'Stock Ledger': 'স্টক লেজার',
    'No recent sales orders.': 'কোনো সাম্প্রতিক বিক্রয় অর্ডার নেই।',
    'No recent purchase orders.': 'কোনো সাম্প্রতিক ক্রয় অর্ডার নেই।',
    'No ledger movements recorded yet.': 'এখনও কোনো স্টক লেনদেন রেকর্ড হয়নি।',
    'Walk-in Customer': 'সাধারণ ক্রেতা',
    'Direct Vendor': 'সরাসরি ভেন্ডর',
    'Ledger entry': 'লেজার এন্ট্রি',
    'After': 'পরে',
    'Total Stock Valuation': 'মোট স্টক মূল্যায়ন',
    'Active Catalog Products': 'সক্রিয় ক্যাটালগ পণ্য',
    'Total Sales Revenue': 'মোট বিক্রয় আয়',
    'Completed Sales Invoices': 'সম্পন্ন বিক্রয় ইনভয়েস',
    'Low Stock Alerts': 'স্বল্প স্টক সতর্কতা',
    'Items requiring reorder': 'পুনরায় অর্ডারের প্রয়োজনীয় পণ্য',
    'Pending Purchase POs': 'অপেক্ষমাণ ক্রয় অর্ডার',
    'Awaiting warehouse receipt': 'পণ্য রিসিভের অপেক্ষায়',
    'Quick Actions': 'দ্রুত কার্যক্রম',
    'New Sale Invoice': 'নতুন বিক্রয় ইনভয়েস',
    'New Purchase PO': 'নতুন ক্রয় অর্ডার',
    'Add New Product': 'নতুন পণ্য যোগ',
    'Point of Sale (POS)': 'পয়েন্ট অব সেল (পিওএস)',
    'Recent Orders & Invoices': 'সাম্প্রতিক অর্ডার ও ইনভয়েস',
    'Sales Orders': 'বিক্রয় অর্ডার',
    'Stock Movements': 'স্টক লেনদেন',
    'Invoice #': 'ইনভয়েস নং',
    'PO #': 'পিও নং',
    'Customer': 'গ্রাহক',
    'Supplier': 'সরবরাহকারী',
    'Amount': 'পরিমাণ',
    'Items': 'আইটেম',
    'Date': 'তারিখ',
    'Paid': 'পরিশোধিত',
    'Partial': 'আংশিক',
    'Unpaid': 'অপরিশোধিত',
    'Received': 'প্রাপ্ত',
    'Stock Breakdown by Category': 'ক্যাটাগরি অনুযায়ী স্টক বিভাজন',
    'Revenue vs Procurement Trend': 'বিক্রয় বনাম ক্রয়ের প্রবণতা',
    'Low Stock Warning': 'স্বল্প স্টকের সতর্কতা',
    'Reorder Level': 'পুনঃঅর্ডার মাত্রা',
    'Current Stock': 'বর্তমান স্টক',
    'Unit': 'একক',
    'View All Products': 'সকল পণ্য দেখুন',
    'View All Sales': 'সকল বিক্রয় দেখুন',
    'View All Purchases': 'সকল ক্রয় দেখুন',
    'Warehouse Stock': 'ওয়্যারহাউস স্টক',
    'Fast': 'দ্রুত',
    'Filter': 'ফিল্টার',
    'Reset': 'রিসেট',
    'Save': 'সংরক্ষণ করুন',
    'Save Changes': 'পরিবর্তন সংরক্ষণ করুন',
    'Cancel': 'বাতিল',
    'Delete': 'মুছুন',
    'Edit': 'সম্পাদনা',
    'View': 'দেখুন',
    'Close': 'বন্ধ করুন',
    'Export': 'এক্সপোর্ট',
    'Print': 'প্রিন্ট',
    'Search': 'অনুসন্ধান',
    'Status': 'অবস্থা',
    'Download PDF': 'পিডিএফ ডাউনলোড',
    'Download Excel': 'এক্সেল ডাউনলোড',
    'Export Excel': 'এক্সেল এক্সপোর্ট',
    'Date Range': 'তারিখের পরিসীমা',
    'All Time': 'সর্বকালীন',
    'Today': 'আজ',
    'This Week': 'এই সপ্তাহ',
    'This Month': 'এই মাস',
    'Last Month': 'গত মাস',
    'This Year': 'এই বছর',
    'Custom': 'কাস্টম',
    'Custom Range': 'নির্দিষ্ট সময়কাল',
    'From Date': 'শুরুর তারিখ',
    'To Date': 'শেষ তারিখ',
    'Apply': 'প্রয়োগ করুন',
    'Batch & Expiry Radar': 'ব্যাচ ও মেয়াদোত্তীর্ণ রাডার',
    'Stock Aging & Dead Stock': 'স্টক এজিং ও ডেড স্টক',
    'Batch Expiry': 'ব্যাচ মেয়াদ',
    'Stock Aging': 'স্টক এজিং',
    'Expired Batches': 'মেয়াদোত্তীর্ণ ব্যাচ',
    'Expiring (< 30 Days)': 'মেয়াদ শেষ (< ৩০ দিন)',
    'Warning (< 90 Days)': 'সতর্কতা (< ৯০ দিন)',
    'Capital at Risk': 'ঝুঁকিপূর্ণ মূলধন',
    'Fresh (0-30 Days)': 'সতেজ (০-৩০ দিন)',
    'Moderate (31-60 Days)': 'স্বাভাবিক (৩১-৬০ দিন)',
    'Slow-Moving (61-90 Days)': 'ধীরগতির (৬১-৯০ দিন)',
    'Dead Stock (90+ Days)': 'ডেড স্টক (৯০+ দিন)',
    'Days Inactive': 'অচল দিন',
    'Frozen Capital': 'স্থবির মূলধন',
    'Aging Bracket': 'এজিং ধাপ',
    'Batch #': 'ব্যাচ নং',
    'Expiry Date': 'মেয়াদোত্তীর্ণ তারিখ',
    'Mfg Date': 'উৎপাদন তারিখ',
    'Days Remaining': 'বাকি দিন',
    'Expiry Status': 'মেয়াদের অবস্থা',
    'All Batches': 'সকল ব্যাচ',
    'All Brackets': 'সকল ধাপ',
    'Healthy': 'সন্তোষজনক',
    'Urgent': 'জরুরি',
    'Dead Stock': 'ডেড স্টক',
    'Fresh': 'সতেজ',
    'Moderate': 'স্বাভাবিক',
    'Slow': 'ধীরগতি',
    'Sales & Profit Margin': 'বিক্রয় ও মোট লাভ',
    'Profitability': 'লাভজনকতা',
    'Total Revenue': 'মোট বিক্রয় আয়',
    'Total COGS': 'মোট পণ্য ব্যয় (COGS)',
    'Gross Profit': 'মোট লাভ (গ্রস প্রফিট)',
    'Gross Margin': 'গ্রস মার্জিন',
    'Units Sold': 'বিক্রীত একক',
    'Avg Selling Price': 'গড় বিক্রয় মূল্য',
    'Margin %': 'মার্জিন %',
    'Performance': 'পারফরম্যান্স',
    'All Performance Levels': 'সকল পারফরম্যান্স স্তর',
    'High Margin (>= 40%)': 'উচ্চ মার্জিন (>= ৪০%)',
    'Healthy (20-39%)': 'সন্তোষজনক (২০-৩৯%)',
    'Low (1-19%)': 'স্বল্প মার্জিন (১-১৯%)',
    'Loss Making (<= 0%)': 'লোকসানজনক (<= ০%)',
    'Revenue': 'বিক্রয় আয়',
    'COGS': 'পণ্য ব্যয়',
    'Financial Summary': 'আর্থিক বিবরণী',
    'Sales & Revenue': 'বিক্রয় ও আয় বিবরণী',
    'Total Sales': 'মোট বিক্রয়',
    'Total Purchases': 'মোট ক্রয়',
    'Total Expenses': 'মোট খরচ',
    'Net Profit': 'নিট লাভ',
    'Net Margin': 'নিট মার্জিন',
    'Operating Cash Flow': 'অপারেটিং নগদ প্রবাহ',
    'Income Statement': 'আয় ও নগদ প্রবাহ বিবরণী',
    'Cash Collections': 'নগদ সংগ্রহ',
    'Customer Receivables': 'গ্রাহক বকেয়া',
    'Supplier Payables': 'সরবরাহকারী বকেয়া',
    'Expense Categories': 'খরচের খাতসমূহ',
    'Recent Sales': 'সাম্প্রতিক বিক্রয়',
    'Recent Purchases': 'সাম্প্রতিক ক্রয়',
    'Recent Expenses': 'সাম্প্রতিক খরচ',
    'Report Scope': 'রিপোর্টের ধরন',
    'All Combined (Executive)': 'সম্মিলিত সারসংক্ষেপ',
    'Sales Report Only': 'শুধুমাত্র বিক্রয় রিপোর্ট',
    'Purchase Report Only': 'শুধুমাত্র ক্রয় রিপোর্ট',
    'Expense Report Only': 'শুধুমাত্র খরচ রিপোর্ট',
    'Revenue & Profit Only': 'শুধুমাত্র আয় ও লাভ বিবরণী',
    'Download Sales PDF': 'বিক্রয় রিপোর্ট PDF',
    'Download Sales Excel': 'বিক্রয় রিপোর্ট Excel',
    'Download Purchase PDF': 'ক্রয় রিপোর্ট PDF',
    'Download Purchase Excel': 'ক্রয় রিপোর্ট Excel',
    'Download Expense PDF': 'খরচ রিপোর্ট PDF',
    'Download Expense Excel': 'খরচ রিপোর্ট Excel',
    'Download Revenue PDF': 'আয় ও লাভ বিবরণী PDF',
    'Download Revenue Excel': 'আয় ও লাভ বিবরণী Excel',
};

// Replace {placeholders}. In Bengali, numbers become Bengali digits.
function interpolate(text, params, lang) {
    if (!params) return text;
    return text.replace(/\{(\w+)\}/g, (match, name) => {
        if (!(name in params)) return match;
        const value = params[name];
        const isNumeric = typeof value === 'number' || (typeof value === 'string' && /^\d+$/.test(value));
        return lang === 'bn' && isNumeric ? toBnDigits(value) : value;
    });
}

// Usage:
//   t('Save')
//   t('Across {n} active catalog products', { n: 5 })
//   t('Save', 'fallback text')
//   t('Across {n} active catalog products', 'fallback {n}', { n: 5 })
export function t(key, fallbackOrParams = null, maybeParams = null) {
    if (!key) return '';

    let fallback = null;
    let params = null;
    if (fallbackOrParams && typeof fallbackOrParams === 'object') {
        params = fallbackOrParams;
    } else {
        fallback = fallbackOrParams;
        params = maybeParams;
    }

    const lang = currentLang.value;
    const text = lang === 'bn'
        ? (dictionary[key] || fallback || key)
        : (fallback || key);

    return interpolate(text, params, lang);
}

export function useTranslation() {
    return {
        currentLang,
        setLang,
        toggleLang,
        t,
        toBnDigits,
        dictionary,
    };
}
