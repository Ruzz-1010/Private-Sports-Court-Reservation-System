{{--
    Reusable booking detail rows for the transactional emails.

    Included with @include rather than used as an anonymous component so the
    partial stays resolvable from any view path. Inline styles only: email
    clients strip <style> blocks and reject classes.

    Expects $rows = list of ['label' => string, 'value' => string]
--}}

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
    @foreach ($rows as $row)
        <tr>
            <td valign="top" style="padding:9px 0;border-bottom:1px solid #E2E8F0;font-size:14px;color:#64748B;width:42%;">
                {{ $row['label'] }}
            </td>
            <td valign="top" align="right" style="padding:9px 0;border-bottom:1px solid #E2E8F0;font-size:14px;color:#0F172A;font-weight:600;">
                {{ $row['value'] }}
            </td>
        </tr>
    @endforeach
</table>
