<!DOCTYPE html>
<html>
<head>
    <title>RAS  PDF</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 20px;
        }

        .banner {
            width: 100%;
            height: auto;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th, td {
            text-align: left;
            padding: 6px;
            vertical-align: top;
        }

        .bordered {
            border: 1px solid #000;
        }

        table.header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .header-table th {
            font-weight: normal;
            border: 1px solid #000;
            text-align: center; 
        }

        .padding-left {
            padding-left: 50px; /* 👈 Adjust value as needed */
            text-align: left;   /* optional, ensures text starts from left */
        }
    </style>
</head>
<body>
    {{-- Banner Image --}}
    <img src="{{ public_path('img/caap-banner.png') }}" class="banner" alt="Banner" width="100%">

     <table class="header-table">
        <tr text-align="center">
            <th class="bordered" width="30%">
                Originating Office<br>
            </th>
            <th width="40%"><strong>{{ $record->origin ?? 'N/A' }}</strong></th>
            <th class="bordered" width="30%">
                Reference Number<br>
                <strong>{{ $record->reference ?? 'N/A' }}</strong>
            </th>
        </tr>

        <tr>
            <td rowspan="2" colspan="2" class="bordered" width="60%">Subject: <br> <strong>
                <p class="padding-left">{{ $record->subject ?? '' }}</p>
            </strong></td>
            <td class="bordered" width="40%">Date of Document  : <br>
                <strong>{{ $record->created_at ?? '' }}</strong>
            </td>
        </tr>
        <tr>     
            <td class="bordered" width="40%"> Date of Document   <br><br><br>                
            </td>
        </tr>
    </table>
    <table class="header-table">
        <tr text-align="center">
            <th class="bordered" width="20%" rowspan="2"><br><br><strong>DATE/TIME </strong></th>
            <th width="20%"><strong>FROM</strong></th>            
            <th width="20%"><strong>TO</strong></th>
            <th class="bordered" width="60%" rowspan="2"><br><strong> REMARKS / INSTRUCTIONS / ACTION REQUESTED</strong></th>
        </tr>
        <tr text-align="center">
            <th width="20%">Name and Position of Official</th>            
            <th width="20%">Name and Position of Official</th>
        </tr>

        {{-- Display Transactions --}}
        @php
            $totalRows = 7; // target total rows
            $transactionCount = $record->transactions->count();
            $emptyRows = $totalRows - $transactionCount;
        @endphp

        @if($transactionCount > 0)
            @foreach($record->transactions as $transaction)
                <tr style="text-align: center;">
                    <td class="bordered" width="20%">
                        {{ \Carbon\Carbon::parse($transaction->created_at)->format('F j, Y g:i A') }}
                    </td>
                    <th class="bordered" width="20%">
                        {{ $transaction->office }}
                    </td>
                    <th class="bordered" width="20%">
                        {{ $transaction->destination }}
                    </td>
                    <td class="bordered relative align-bottom w-[60%]">
                        <strong>{{ $transaction->remarks }}</strong>
                        <p class="text-xs absolute bottom-1 right-2">
                           Recieved by: {{ $transaction->recieved_by }} <br>
                           Date recieved: {{ \Carbon\Carbon::parse($transaction->date_recieved)->format('F j, Y g:i A') }}
                        </p>
                    </td>
                </tr>
            @endforeach
        @endif

        {{-- Fill empty rows if fewer than 7 --}}
        @for($i = 0; $i < $emptyRows; $i++)
            <tr style="text-align: center;">
                <td class="bordered" width="20%"><br><br><br></td>
                <td class="bordered" width="20%"></td>
                <td class="bordered" width="20%"></td>
                <td class="bordered" width="40%"></td>
            </tr>
        @endfor
    </table>
</body>
</html>
