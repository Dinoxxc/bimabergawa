<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Document Authenticity Verification | PT. Panca Bima Bergawa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        h1, h2, h3, .font-heading {
            font-family: 'Outfit', sans-serif;
        }
    </style>
</head>
<body class="bg-zinc-50 text-zinc-900 min-h-screen flex flex-col justify-between antialiased selection:bg-zinc-900 selection:text-white">
    <!-- Header / Brand Bar -->
    <header class="border-b border-zinc-200 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded bg-zinc-900 flex items-center justify-center text-white font-heading font-bold text-sm tracking-wider">
                    PBB
                </div>
                <div>
                    <div class="font-heading font-bold text-base tracking-tight text-zinc-900">PT. PANCA BIMA BERGAWA</div>
                    <div class="text-xs text-zinc-500 font-mono">B2B Industrial Procurement Partner</div>
                </div>
            </div>
            <div class="text-xs font-mono uppercase px-2.5 py-1 bg-zinc-100 rounded border border-zinc-200 text-zinc-700">
                Digital Verification
            </div>
        </div>
    </header>

    <!-- Main Verification Section -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 py-10 flex-grow w-full">
        @if ($isValid && $doc)
            <!-- Authentic Document Badge -->
            <div class="bg-white border border-zinc-200 rounded-lg shadow-sm p-6 sm:p-8">
                <div class="flex items-center justify-between pb-6 border-b border-zinc-200">
                    <div>
                        <span class="inline-flex items-center px-3 py-1 rounded text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono tracking-wide">
                            [OFFICIALLY VERIFIED &amp; AUTHENTIC]
                        </span>
                        <h1 class="text-2xl font-bold font-heading text-zinc-900 mt-2">
                            {{ $doc['type'] }}
                        </h1>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-zinc-500 font-mono uppercase">Document Reference</div>
                        <div class="text-lg font-mono font-bold text-zinc-900">{{ $doc['document_number'] }}</div>
                    </div>
                </div>

                <!-- Attributes Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 py-6 border-b border-zinc-200 text-sm">
                    <div>
                        <div class="text-xs font-medium text-zinc-500 uppercase tracking-wider">Recipient Entity / Client</div>
                        <div class="text-base font-semibold text-zinc-900 mt-1">{{ $doc['client_company'] }}</div>
                        @if (!empty($doc['customer_name']))
                            <div class="text-xs text-zinc-600 mt-0.5">Attn: {{ $doc['customer_name'] }}</div>
                        @endif
                    </div>
                    <div>
                        <div class="text-xs font-medium text-zinc-500 uppercase tracking-wider">Issuance & Timestamp</div>
                        <div class="text-base font-semibold text-zinc-900 mt-1 font-mono">{{ $doc['created_at'] }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-zinc-500 uppercase tracking-wider">Transaction Valuation / Scope</div>
                        <div class="text-base font-bold text-zinc-900 mt-1 font-mono">{{ $doc['total_amount'] }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">Line Items: {{ $doc['items_count'] }} recorded product(s)</div>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-zinc-500 uppercase tracking-wider">Corporate Authorization</div>
                        <div class="text-base font-semibold text-zinc-900 mt-1">{{ $doc['authorized_by'] }}</div>
                        <div class="text-xs text-emerald-600 font-mono mt-0.5">Status: {{ $doc['status'] }}</div>
                    </div>
                </div>

                <!-- Issuer & Security Confirmation -->
                <div class="pt-6">
                    <div class="text-xs text-zinc-500 uppercase tracking-wider font-medium">Digital Signature Fingerprint</div>
                    <div class="mt-1 font-mono text-xs text-zinc-600 bg-zinc-50 p-2.5 rounded border border-zinc-200 break-all select-all">
                        {{ $hash }}
                    </div>
                    <div class="mt-4 p-3 bg-zinc-50 rounded border border-zinc-200 text-xs text-zinc-600 leading-relaxed">
                        <strong class="text-zinc-800">Security Guarantee:</strong> This transactional document was issued and verified through the encrypted corporate ERP infrastructure of PT. Panca Bima Bergawa. If the printed parameters do not match this public verification portal, please immediately report to <span class="font-mono text-zinc-800">compliance@bimabergawa.com</span>.
                    </div>
                </div>
            </div>
        @else
            <!-- Unverified / Invalid Document -->
            <div class="bg-white border border-red-200 rounded-lg shadow-sm p-6 sm:p-8 text-center">
                <div class="inline-flex items-center px-3 py-1 rounded text-xs font-semibold bg-red-50 text-red-700 border border-red-200 font-mono tracking-wide">
                    [UNVERIFIED OR INVALID DOCUMENT]
                </div>
                <h1 class="text-2xl font-bold font-heading text-zinc-900 mt-3">
                    Verification Check Failed
                </h1>
                <p class="text-sm text-zinc-600 max-w-md mx-auto mt-2 leading-relaxed">
                    The requested verification record could not be validated against the active or historical corporate database of PT. Panca Bima Bergawa.
                </p>
                <div class="mt-6 p-4 bg-zinc-50 rounded border border-zinc-200 text-left font-mono text-xs text-zinc-700 max-w-md mx-auto">
                    <div>Provided Type: <span class="font-bold text-zinc-900">{{ $type }}</span></div>
                    <div class="truncate">Hash: {{ $hash }}</div>
                </div>
            </div>
        @endif
    </main>

    <!-- Corporate Footer -->
    <footer class="border-t border-zinc-200 bg-white text-xs text-zinc-500 py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between space-y-2 sm:space-y-0">
            <div>
                &copy; {{ date('Y') }} PT. Panca Bima Bergawa. Head Office: Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920.
            </div>
            <div class="font-mono text-zinc-400">
                PBB-DOC-VERIFY-v1.1
            </div>
        </div>
    </footer>
</body>
</html>
