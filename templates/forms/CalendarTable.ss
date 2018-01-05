<table cellpadding="0" cellspacing="0" class="calendar table">
    <tr>
        <th><a href="$BackLink">&lt;&lt;</a></td>
        <th colspan="3" class="text-center">$MonthField</td>
        <th colspan="2" class="text-center">$YearField</td>
        <th class="text-right"><a href="$NextLink">&gt;&gt;</a></td>
    </tr>
	<tr class="calendar-row">
        <% loop $DayHeadings %>
            <th class="calendar-day-head">
                $Day
            </th>
        <% end_loop %>
    </tr>
    <tr class="calendar-row">
        <% loop $Days %>
            <% if not $InMonth %>
                <td class="calendar-day-np"> </td>
            <% else %>
                <td class="calendar-day">
                    <div class="day-number">$Number</div>
                </td>
            <% end_if %>
            <% if $MultipleOf(7) && not $Last %>
                </tr><tr class="calendar-row">
            <% end_if %>
        <% end_loop %>
    </tr>
</table>