@php
    $adminFee = rtrim(rtrim(number_format(config('reservation.refund.admin_fee_percent'), 2), '0'), '.');
    $feeAmount = 1000 * config('reservation.refund.admin_fee_percent') / 100;
@endphp

<div class="w-full overflow-hidden rounded-lg border border-white/[.07] bg-white/[.02]">

    <details class="group border-b border-white/[.06] open:bg-white/[.02]" open>
        <summary class="flex cursor-pointer list-none items-start gap-2 px-3 py-2.5 [&::-webkit-details-marker]:hidden sm:items-center sm:px-3.5">
            <span class="text-[9px] font-bold tabular-nums text-lime-300/70">01</span>
            <h2 class="min-w-0 flex-1 break-words text-[11px] font-black uppercase leading-4 tracking-[.1em] text-lime-300">About the facility</h2>
            <span class="text-[13px] leading-none text-slate-500 transition group-open:rotate-45" aria-hidden="true">+</span>
        </summary>
        <p class="break-words px-3 pb-3 text-[11px] leading-5 text-slate-400 sm:px-3.5">
            {{ config('reservation.facility_name') }} is a private pickleball facility at
            {{ config('reservation.facility_address') }}. This system is run by management to reserve
            courts, record payments, and issue official receipts.
        </p>
    </details>

    <details class="group border-b border-white/[.06] open:bg-white/[.02]">
        <summary class="flex cursor-pointer list-none items-start gap-2 px-3 py-2.5 [&::-webkit-details-marker]:hidden sm:items-center sm:px-3.5">
            <span class="text-[9px] font-bold tabular-nums text-lime-300/70">02</span>
            <h2 class="min-w-0 flex-1 break-words text-[11px] font-black uppercase leading-4 tracking-[.1em] text-lime-300">Your account</h2>
            <span class="text-[13px] leading-none text-slate-500 transition group-open:rotate-45" aria-hidden="true">+</span>
        </summary>
        <ul class="space-y-1.5 break-words px-3 pb-3 text-[11px] leading-5 text-slate-400 sm:px-3.5">
            <li>&bull; Give your real full name and a working email. Confirmations and receipts go there.</li>
            <li>&bull; One account per person. Keep your password private; you answer for every booking under your account.</li>
            <li>&bull; Tell management right away if someone used your account without permission.</li>
            <li>&bull; False details, duplicate accounts, or hoarding slots may lead to suspension.</li>
        </ul>
    </details>

    <details class="group border-b border-white/[.06] open:bg-white/[.02]">
        <summary class="flex cursor-pointer list-none items-start gap-2 px-3 py-2.5 [&::-webkit-details-marker]:hidden sm:items-center sm:px-3.5">
            <span class="text-[9px] font-bold tabular-nums text-lime-300/70">03</span>
            <h2 class="min-w-0 flex-1 break-words text-[11px] font-black uppercase leading-4 tracking-[.1em] text-lime-300">Court hours and schedule</h2>
            <span class="text-[13px] leading-none text-slate-500 transition group-open:rotate-45" aria-hidden="true">+</span>
        </summary>
        <ul class="space-y-1.5 break-words px-3 pb-3 text-[11px] leading-5 text-slate-400 sm:px-3.5">
            <li>&bull; Open daily <strong class="text-white">9:00 AM to 9:00 PM</strong>, closed every <strong class="text-white">Sunday</strong>.</li>
            <li>&bull; Slots follow <strong class="text-white">{{ config('reservation.increment_minutes') }}-minute</strong> increments.</li>
            <li>&bull; One booking runs from <strong class="text-white">{{ config('reservation.min_duration_minutes') / 60 }} hour</strong> to <strong class="text-white">{{ config('reservation.max_duration_minutes') / 60 }} hours</strong>.</li>
            <li>&bull; Book up to <strong class="text-white">{{ config('reservation.advance_booking_days') }} days</strong> ahead, until <strong class="text-white">{{ config('reservation.lead_time_minutes') }} minutes</strong> before the slot starts.</li>
            <li>&bull; Management may block a court for maintenance or private events. An affected booking is moved or refunded.</li>
        </ul>
    </details>

    <details class="group border-b border-white/[.06] open:bg-white/[.02]">
        <summary class="flex cursor-pointer list-none items-start gap-2 px-3 py-2.5 [&::-webkit-details-marker]:hidden sm:items-center sm:px-3.5">
            <span class="text-[9px] font-bold tabular-nums text-lime-300/70">04</span>
            <h2 class="min-w-0 flex-1 break-words text-[11px] font-black uppercase leading-4 tracking-[.1em] text-lime-300">Reservations and slot holds</h2>
            <span class="text-[13px] leading-none text-slate-500 transition group-open:rotate-45" aria-hidden="true">+</span>
        </summary>
        <ul class="space-y-1.5 break-words px-3 pb-3 text-[11px] leading-5 text-slate-400 sm:px-3.5">
            <li>&bull; A new booking is <strong class="text-white">pending</strong> and holds the slot only for a limited time.</li>
            <li>&bull; Online payment holds the slot for <strong class="text-white">{{ config('reservation.hold_minutes.online') }} minutes</strong>.</li>
            <li>&bull; On-site payment holds the slot until the day of play, but never past the start time. Unpaid bookings are released.</li>
            <li>&bull; Two bookings can never cover the same court and time. Overlapping reservations are rejected automatically.</li>
            <li>&bull; A reservation is <strong class="text-white">confirmed</strong> only after the payment is verified &mdash; online by the provider, or on-site by staff.</li>
        </ul>
    </details>

    <details class="group border-b border-white/[.06] open:bg-white/[.02]">
        <summary class="flex cursor-pointer list-none items-start gap-2 px-3 py-2.5 [&::-webkit-details-marker]:hidden sm:items-center sm:px-3.5">
            <span class="text-[9px] font-bold tabular-nums text-lime-300/70">05</span>
            <h2 class="min-w-0 flex-1 break-words text-[11px] font-black uppercase leading-4 tracking-[.1em] text-lime-300">Payment</h2>
            <span class="text-[13px] leading-none text-slate-500 transition group-open:rotate-45" aria-hidden="true">+</span>
        </summary>
        <ul class="space-y-1.5 break-words px-3 pb-3 text-[11px] leading-5 text-slate-400 sm:px-3.5">
            <li>&bull; Rates are per hour in Philippine pesos and depend on the court you choose.</li>
            <li>&bull; Online payment goes through the facility's provider (QR Ph checkout). The facility never stores your card or wallet details.</li>
            <li>&bull; On-site payment is settled at the court management desk before play.</li>
            <li>&bull; After booking you get an email with a PDF copy. While payment is unverified the PDF is stamped <strong class="text-white">PENDING PAYMENT</strong> and is not yet an official receipt.</li>
            <li>&bull; Once verified, a confirmation email with the paid receipt is sent. Show that receipt, or its reference number, at court management.</li>
            <li>&bull; A payment that arrives after a hold expires does not reclaim the slot. Ask management for a refund or rebooking.</li>
        </ul>
    </details>

    <details class="group border-b border-white/[.06] open:bg-white/[.02]">
        <summary class="flex cursor-pointer list-none items-start gap-2 px-3 py-2.5 [&::-webkit-details-marker]:hidden sm:items-center sm:px-3.5">
            <span class="text-[9px] font-bold tabular-nums text-lime-300/70">06</span>
            <h2 class="min-w-0 flex-1 break-words text-[11px] font-black uppercase leading-4 tracking-[.1em] text-lime-300">Cancellation and refunds</h2>
            <span class="text-[13px] leading-none text-slate-500 transition group-open:rotate-45" aria-hidden="true">+</span>
        </summary>
        <ul class="space-y-1.5 break-words px-3 pb-3 text-[11px] leading-5 text-slate-400 sm:px-3.5">
            <li>&bull; You may cancel your own booking while it is pending or confirmed and has not started.</li>
            <li>&bull; Cancelling a pending booking releases the slot and closes its unpaid payment attempt.</li>
            <li>&bull; Unpaid bookings are cancelled automatically when the hold expires.</li>
        </ul>
        <p class="px-3 pb-1 text-[10px] font-black uppercase tracking-[.1em] text-white sm:px-3.5">Refund of an online payment</p>
        <ul class="space-y-1.5 break-words px-3 pb-3 text-[11px] leading-5 text-slate-400 sm:px-3.5">
            <li>&bull; Paid online but cannot play? Ask for a refund at least <strong class="text-white">{{ config('reservation.refund.request_deadline_hours') }} hours</strong> before your slot.</li>
            <li>&bull; A <strong class="text-white">{{ $adminFee }}% administration fee</strong> is deducted from what you paid. It covers the payment provider's charge and the court time held for you.</li>
            <li>&bull; Example: pay <strong class="text-white">&#8369;1,000.00</strong>, administration fee <strong class="text-white">&#8369;{{ number_format($feeAmount, 2) }}</strong>, and <strong class="text-white">&#8369;{{ number_format(1000 - $feeAmount, 2) }}</strong> is returned to you.</li>
            <li>&bull; Management approves and releases refunds at the desk. Bring your reference number or receipt. Provider or bank processing time applies.</li>
            <li>&bull; No refund once your booked time has started, or for a booking you did not show up for.</li>
            <li>&bull; If management cancels or blocks the court for maintenance or a private event, you get a <strong class="text-white">full refund with no administration fee</strong>, or a free rebooking.</li>
        </ul>
    </details>

    <details class="group border-b border-white/[.06] open:bg-white/[.02]">
        <summary class="flex cursor-pointer list-none items-start gap-2 px-3 py-2.5 [&::-webkit-details-marker]:hidden sm:items-center sm:px-3.5">
            <span class="text-[9px] font-bold tabular-nums text-lime-300/70">07</span>
            <h2 class="min-w-0 flex-1 break-words text-[11px] font-black uppercase leading-4 tracking-[.1em] text-lime-300">Arrival and use of the courts</h2>
            <span class="text-[13px] leading-none text-slate-500 transition group-open:rotate-45" aria-hidden="true">+</span>
        </summary>
        <ul class="space-y-1.5 break-words px-3 pb-3 text-[11px] leading-5 text-slate-400 sm:px-3.5">
            <li>&bull; Arrive a few minutes early and present your reservation reference or receipt.</li>
            <li>&bull; Your booked time is fixed. Late arrival does not extend the session.</li>
            <li>&bull; Paid on site? Settle it at the desk <strong class="text-white">before</strong> you play so the booking becomes confirmed.</li>
            <li>&bull; Wear proper court shoes. Bring your own paddle; loaners depend on availability.</li>
            <li>&bull; No smoking, alcohol, or glass containers inside the courts. Keep the area clean.</li>
            <li>&bull; Follow the instructions of facility staff at all times.</li>
        </ul>
    </details>

    <details class="group border-b border-white/[.06] open:bg-white/[.02]">
        <summary class="flex cursor-pointer list-none items-start gap-2 px-3 py-2.5 [&::-webkit-details-marker]:hidden sm:items-center sm:px-3.5">
            <span class="text-[9px] font-bold tabular-nums text-lime-300/70">08</span>
            <h2 class="min-w-0 flex-1 break-words text-[11px] font-black uppercase leading-4 tracking-[.1em] text-lime-300">No-shows and account suspension</h2>
            <span class="text-[13px] leading-none text-slate-500 transition group-open:rotate-45" aria-hidden="true">+</span>
        </summary>
        <ul class="space-y-1.5 break-words px-3 pb-3 text-[11px] leading-5 text-slate-400 sm:px-3.5">
            <li>&bull; A <strong class="text-white">no-show</strong> means you booked a slot and did not arrive for your booked date and time without cancelling.</li>
            <li>&bull; Staff record no-shows at the desk. Each one counts against the account for <strong class="text-white">{{ config('reservation.no_show.counting_window_days') }} days</strong>.</li>
            <li>&bull; After <strong class="text-white">{{ config('reservation.no_show.limit_before_suspension') }} no-shows</strong> management may <strong class="text-white">suspend</strong> the account, and no new reservations can be made while suspended.</li>
            <li>&bull; Cannot make it? Cancel in the system as early as possible. A cancelled booking is not a no-show and frees the slot for others.</li>
            <li>&bull; Think a no-show was recorded by mistake? Talk to management so they can review it.</li>
            <li>&bull; Unpaid on-site bookings that are never settled are released and may also count as a no-show.</li>
        </ul>
    </details>

    <details class="group border-b border-white/[.06] open:bg-white/[.02]">
        <summary class="flex cursor-pointer list-none items-start gap-2 px-3 py-2.5 [&::-webkit-details-marker]:hidden sm:items-center sm:px-3.5">
            <span class="text-[9px] font-bold tabular-nums text-lime-300/70">09</span>
            <h2 class="min-w-0 flex-1 break-words text-[11px] font-black uppercase leading-4 tracking-[.1em] text-lime-300">Safety and liability</h2>
            <span class="text-[13px] leading-none text-slate-500 transition group-open:rotate-45" aria-hidden="true">+</span>
        </summary>
        <p class="break-words px-3 pb-3 text-[11px] leading-5 text-slate-400 sm:px-3.5">
            Pickleball is a physical sport. You play at your own risk and are responsible for your own
            health, belongings, and the safety of your guests. The facility is not liable for injuries
            or for lost or damaged property, except where the law says otherwise. Report any accident
            or damage to staff immediately.
        </p>
    </details>

    <details class="group border-b border-white/[.06] open:bg-white/[.02]">
        <summary class="flex cursor-pointer list-none items-start gap-2 px-3 py-2.5 [&::-webkit-details-marker]:hidden sm:items-center sm:px-3.5">
            <span class="text-[9px] font-bold tabular-nums text-lime-300/70">10</span>
            <h2 class="min-w-0 flex-1 break-words text-[11px] font-black uppercase leading-4 tracking-[.1em] text-lime-300">Your data</h2>
            <span class="text-[13px] leading-none text-slate-500 transition group-open:rotate-45" aria-hidden="true">+</span>
        </summary>
        <p class="break-words px-3 pb-3 text-[11px] leading-5 text-slate-400 sm:px-3.5">
            The system keeps your name, email, reservations, and payment records so bookings can be
            confirmed, receipts issued, and reports prepared. Payment processing is handled by the
            facility's payment provider. Your details are not sold and are shared only with staff who
            need them to run the courts. You may ask management to correct your details.
        </p>
    </details>

    <details class="group border-b border-white/[.06] open:bg-white/[.02]">
        <summary class="flex cursor-pointer list-none items-start gap-2 px-3 py-2.5 [&::-webkit-details-marker]:hidden sm:items-center sm:px-3.5">
            <span class="text-[9px] font-bold tabular-nums text-lime-300/70">11</span>
            <h2 class="min-w-0 flex-1 break-words text-[11px] font-black uppercase leading-4 tracking-[.1em] text-lime-300">Changes to these terms</h2>
            <span class="text-[13px] leading-none text-slate-500 transition group-open:rotate-45" aria-hidden="true">+</span>
        </summary>
        <p class="break-words px-3 pb-3 text-[11px] leading-5 text-slate-400 sm:px-3.5">
            Management may update these terms, hours, or rates when needed. The current version is
            always shown on this page. A booking you already paid for keeps the terms that applied
            when you made it.
        </p>
    </details>

    <details class="group open:bg-white/[.02]">
        <summary class="flex cursor-pointer list-none items-start gap-2 px-3 py-2.5 [&::-webkit-details-marker]:hidden sm:items-center sm:px-3.5">
            <span class="text-[9px] font-bold tabular-nums text-lime-300/70">12</span>
            <h2 class="min-w-0 flex-1 break-words text-[11px] font-black uppercase leading-4 tracking-[.1em] text-lime-300">Contact</h2>
            <span class="text-[13px] leading-none text-slate-500 transition group-open:rotate-45" aria-hidden="true">+</span>
        </summary>
        <p class="break-words px-3 pb-3 text-[11px] leading-5 text-slate-400 sm:px-3.5">
            Questions about a booking, a payment, or these terms? Talk to the court management desk at
            {{ config('reservation.facility_address') }}.
        </p>
    </details>
</div>
