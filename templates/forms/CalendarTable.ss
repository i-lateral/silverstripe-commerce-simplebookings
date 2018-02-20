<% require css(commerce-simplebookings/css/calendar.css) %>

<% require javascript(framework/thirdparty/jquery/jquery.js) %>
<% require javascript(commerce-simplebookings/js/calendar.js) %>
<div $AttributesHTML>
    <table cellspacing="0" cellpadding="0" class="calendar table">
        <tr>
            <th><a class="direction-link" href="$BackLink">&lt;&lt;</a></td>
            <th colspan="5">
                <div class="line row">
                    <div class="col-xs-6 size1of2 unit">$MonthField</div>
                    <div class="text-right size1of2 col-xs-6 unit">$YearField</div>
                </div>
            </td>
            <th class="text-right"><a class="direction-link" href="$NextLink">&gt;&gt;</a></td>
        </tr>
        <tr class="calendar-row">
            <% loop $DayHeadings %>
                <th class="calendar-day-head">
                    $Day
                </th>
            <% end_loop %>
        </tr>
        <tr class="calendar-row text-center">
            <% loop $Days %>
                <% if not $InMonth %>
                    <td data-date="$Date" data-spaces="$Spaces" class="calendar-day-np $Availability">$Number </td>
                <% else %>
                    <td data-date="$Date" data-spaces="$Spaces" class="calendar-day $Availability">
                        <div class="day-number">$Number</div>
                    </td>
                <% end_if %>
                <% if $MultipleOf(7) && not $Last %>
                    </tr><tr class="calendar-row text-center">
                <% end_if %>
            <% end_loop %>
        </tr>
    </table>

    <div id="CalendarStart">
        $StartField
    </div>
    <div id="CalendarEnd">
        $EndField
    </div>
</div>    