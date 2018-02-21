<% require css(calendarfield/css/calendar.css) %>

<% require javascript(framework/thirdparty/jquery/jquery.js) %>
<% require javascript(calendarfield/js/calendar.js) %>
<% require javascript(commerce-simplebookings/js/booking-calendar.js) %>
<div $AttributesHTML>
    <table cellspacing="0" cellpadding="0" class="calendar table">
        <tr>
            <th><a class="direction-link" href="$BackLink">&lt;&lt;</a></td>
            <th colspan="5">
                <div class="line row no-gutters">
                    <div class="col-xs-6 size1of2 unit">$MonthField</div>
                    <div class="text-right size1of2 col-xs-6 unit">$YearField</div>
                </div>
            </td>
            <th><a class="direction-link" href="$NextLink">&gt;&gt;</a></td>
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
                    <td data-date="$Date" data-spaces="$Spaces" data-lock="$Lock" class="calendar-day-np $Availability">$Number </td>
                <% else %>
                    <td data-date="$Date" data-spaces="$Spaces" data-lock="$Lock" class="calendar-day $Availability">
                        <div class="day-number">$Number</div>
                    </td>
                <% end_if %>
                <% if $MultipleOf(7) && not $Last %>
                    </tr><tr class="calendar-row text-center">
                <% end_if %>
            <% end_loop %>
        </tr>
    </table>

    <div class="calendar-key">
        <div class="row line">
            <div class="unit size1of2 col-xs-6">
                <p><span class="color-swatch available"></span> Available</p>
            </div>
            <div class="unit size1of2 col-xs-6">
                <p><span class="color-swatch not-available"></span> Unavailable</p>
            </div>
            <div class="unit size1of2 col-xs-6">
                <p><span class="color-swatch selected"></span> Selected</p>
            </div>
        </div>
    </div>

    <% loop $Children %>
        $FieldHolder
    <% end_loop %>
</div>    