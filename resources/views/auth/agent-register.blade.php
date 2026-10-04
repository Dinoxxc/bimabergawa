<!DOCTYPE html>
<html lang="en" class="h-full bg-zinc-50 dark:bg-zinc-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freelance Partner Registration - PT. Panca Bima Bergawa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        h1, h2, h3, .font-heading {
            font-family: 'Outfit', sans-serif;
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8 text-zinc-900 dark:text-zinc-100">

    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-zinc-900 text-white font-heading font-bold text-xl shadow-xs dark:bg-zinc-100 dark:text-zinc-900">
            PBB
        </div>
        <h2 class="mt-4 text-2xl font-bold tracking-tight font-heading">
            Partner Network Registration
        </h2>
        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
            Commercial Freelance Sales Portal & Commission Program
        </p>
    </div>

    <div class="mt-6 sm:mx-auto sm:w-full sm:max-w-2xl px-4 sm:px-0">
        <div class="bg-white dark:bg-zinc-900 py-8 px-6 shadow-sm border border-zinc-200 dark:border-zinc-800 sm:rounded-2xl sm:px-10">

            @if(session('success'))
                <div class="mb-6 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 p-4 border border-emerald-200 dark:border-emerald-800 text-xs text-emerald-800 dark:text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 rounded-lg bg-rose-50 dark:bg-rose-950/40 p-4 border border-rose-200 dark:border-rose-800 text-xs text-rose-800 dark:text-rose-300">
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('agent.register.submit') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <!-- Section: Personal Identity -->
                <div class="border-b border-zinc-100 dark:border-zinc-800/80 pb-5">
                    <h3 class="text-sm font-semibold font-heading text-zinc-900 dark:text-zinc-100">
                        1. Personal Identity & Credentials
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Legal personal information for formal commercial onboarding.
                    </p>

                    <div class="mt-4 grid grid-cols-1 gap-y-4 gap-x-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">Full Legal Name *</label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                class="mt-1 block w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-xs text-zinc-900 dark:text-zinc-100 shadow-2xs focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">Login Email *</label>
                            <input type="email" name="email" value="{{ old('email') }}" required
                                class="mt-1 block w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-xs text-zinc-900 dark:text-zinc-100 shadow-2xs focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">WhatsApp / Phone *</label>
                            <input type="tel" name="phone" value="{{ old('phone') }}" required placeholder="e.g. 081234567890"
                                class="mt-1 block w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-xs text-zinc-900 dark:text-zinc-100 shadow-2xs focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">NIK KTP (16 Digits) *</label>
                            <input type="text" name="nik" value="{{ old('nik') }}" required maxlength="16" placeholder="3171012345670001"
                                class="mt-1 block w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-xs text-zinc-900 dark:text-zinc-100 shadow-2xs font-mono focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">NPWP Tax ID (Optional)</label>
                            <input type="text" name="npwp_number" value="{{ old('npwp_number') }}" placeholder="01.234.567.8-123.000"
                                class="mt-1 block w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-xs text-zinc-900 dark:text-zinc-100 shadow-2xs font-mono focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">KTP Identity Document *</label>
                            <input type="file" name="ktp_file" required accept=".jpg,.jpeg,.png,.pdf"
                                class="mt-1 block w-full text-xs text-zinc-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-800 dark:file:text-zinc-300">
                            <span class="text-[10px] text-zinc-400">JPG, PNG, or PDF (max 5 MB).</span>
                        </div>
                    </div>
                </div>

                <!-- Section: Banking & Disbursement -->
                <div class="border-b border-zinc-100 dark:border-zinc-800/80 pb-5">
                    <h3 class="text-sm font-semibold font-heading text-zinc-900 dark:text-zinc-100">
                        2. Banking & Payout Destination
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Encrypted at-rest (UU PDP No. 27/2022) for automated commission disbursements.
                    </p>

                    <div class="mt-4 grid grid-cols-1 gap-y-4 gap-x-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">Bank Destination *</label>
                            <input type="text" name="bank_name" value="{{ old('bank_name') }}" required placeholder="e.g. BCA, Mandiri, BNI, BRI"
                                class="mt-1 block w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-xs text-zinc-900 dark:text-zinc-100 shadow-2xs focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">Account Number *</label>
                            <input type="text" name="bank_account_number" value="{{ old('bank_account_number') }}" required placeholder="e.g. 1234567890"
                                class="mt-1 block w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-xs text-zinc-900 dark:text-zinc-100 shadow-2xs font-mono focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">Account Holder Name *</label>
                            <input type="text" name="bank_account_name" value="{{ old('bank_account_name') }}" required placeholder="Must match registered KTP name"
                                class="mt-1 block w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-xs text-zinc-900 dark:text-zinc-100 shadow-2xs focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                        </div>
                    </div>
                </div>

                <!-- Section: Market Focus -->
                <div>
                    <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">Target Industry Focus / Client Segments</label>
                    <input type="text" name="industry_focus" value="{{ old('industry_focus') }}" placeholder="e.g. MEP Contractors, Data Centers, Factory Automation, Building Management"
                        class="mt-1 block w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-xs text-zinc-900 dark:text-zinc-100 shadow-2xs focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                </div>

                <div class="pt-2">
                    <button type="submit"
                        class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-xs text-xs font-semibold text-white bg-zinc-900 hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-900 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200 transition">
                        Submit Partner Application
                    </button>
                </div>

                <div class="text-center pt-2">
                    <a href="{{ route('login') }}" class="text-xs text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 transition">
                        Already an active partner? Sign in via OTP &rarr;
                    </a>
                </div>
            </form>
        </div>

        <p class="mt-6 text-center text-[11px] text-zinc-400">
            PT. Panca Bima Bergawa &bull; Gd. Wirausaha lt.1 Unit 104, Rasuna Said Kav. C5, Jakarta Selatan 12920
        </p>
    </div>

</body>
</html>
