
<style>
    * {
  box-sizing: border-box;
  font-family: Inter, Arial, sans-serif;
}


.page {
  max-width: 900px;
  margin: auto;
}

.hero {
    width:80%;
  background: #0b5cab;
  color: #fff;
  padding: 30px;
  border-radius: 0px 12px 12px 0px;
}

.hero h1 {
    color:white;
  margin: 0 0 10px;
}

.alert {
     color:white;
  margin-top: 15px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.alert span {
  background: red;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

h2 {
  margin: 40px 0 15px;
}

.calendar-card,
.time-card {
  background: #fff;
  padding: 25px;
  border-radius: 14px;
  box-shadow: 0 10px 30px rgba(0,0,0,.08);
}

.calendar-header {
  display: flex;
  justify-content: center;
  gap: 10px;
  margin-bottom: 15px;
}

.weekdays,
.days {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  text-align: center;
}

.days > span {
  min-height: 44px;
}

.weekdays span {
  font-weight: 600;
  color: #666;
}

.days .cal-day {
  padding: 12px;
  cursor: pointer;
  border-radius: 50%;
  border: 2px solid transparent;
  background: transparent;
  font: inherit;
  color: inherit;
  min-width: 44px;
  min-height: 44px;
}

.days .cal-day:hover:not(:disabled):not([aria-disabled="true"]) {
  background: #eee;
}

.days .cal-day:focus-visible {
  outline: 3px solid #0b5cab;
  outline-offset: 2px;
}

.days .cal-day.is-today {
  box-shadow: inset 0 0 0 2px #0b5cab;
  font-weight: 700;
}

.days .cal-day.is-today .cal-day-today-label {
  display: block;
  font-size: 9px;
  font-weight: 700;
  letter-spacing: .02em;
  line-height: 1;
  color: #0b5cab;
  text-transform: uppercase;
}

.days .cal-day.selected {
  background: #e82c2c;
  color: #fff;
  box-shadow: none;
}

.days .cal-day.is-today.selected {
  box-shadow: inset 0 0 0 2px #0b5cab;
}

.days .cal-day.is-today.selected .cal-day-today-label {
  color: #fff;
}

.days .cal-day.is-unavailable,
.days .cal-day:disabled {
  opacity: 0.45;
  cursor: not-allowed;
  text-decoration: line-through;
  pointer-events: none;
}

.times {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 15px;
}

.time {
  padding: 12px 8px;
  background: #f2f2f4;
  border-radius: 10px;
  text-align: center;
  cursor: pointer;
  border: 2px solid transparent;
  font: inherit;
  color: inherit;
  white-space: nowrap;
}

.time:focus-visible {
  outline: 3px solid #0b5cab;
  outline-offset: 2px;
}

.time.selected {
  background: #e82c2c;
  color: #fff;
}

.time.is-unavailable,
.time:disabled {
  opacity: 0.45;
  cursor: not-allowed;
  text-decoration: line-through;
}

.times-status {
  grid-column: 1 / -1;
  margin: 0;
  color: #4b5563;
}

.appointment-error {
  color: #b42318;
  margin: 12px 0 0;
  min-height: 1.2em;
}

.consult-type-fieldset {
  border: 1px solid #d0d5dd;
  border-radius: 12px;
  padding: 18px 16px 10px;
  margin: 0;
}

.consult-type-fieldset legend {
  padding: 0 8px;
  font-size: 14px;
  font-weight: 500;
}

.consult-type-options {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.consult-type-options label {
  position: static;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 16px;
  color: #111;
  font-weight: 500;
  background: none;
  padding: 4px 0;
}

.consult-type-options input {
  width: auto;
  height: auto;
}

.timezone {
  margin: 20px 0;
  color: #777;
}

.actions {
  display: flex;
  justify-content: space-between;
  margin-top: 30px;
}

.btn {
  padding: 12px 28px;
  border-radius: 10px;
  border: none;
  cursor: pointer;
}

.btn.primary {
  background: #0b5cab;
  color: #fff;
}

.btn.ghost {
  background: #eee;
  color: #999;
}


.step {
  display: none;
}

.step.active {
  display: block;
}








.form-wrapper {
  max-width: 520px;
  margin: 40px auto;
}

.dark-form {
  display: flex;
  flex-direction: column;
  gap: 22px;
}

.field {
  position: relative;
}

.field label {
  position: absolute;
  top: -10px;
  left: 16px;
  background: #fff;
  padding: 0 8px;
  font-size: 14px;
  color: #000;
  font-weight: 500;
}

.field input,
.field select {
  width: 100%;
  height: 56px;
  padding: 0 18px;
  background: transparent;
  
  border-radius: 12px;
  color: #bbb;
  font-size: 16px;
  outline: none;
}

.field input::placeholder {
  color: #bbb;
}

.field select {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg width='14' height='8' viewBox='0 0 14 8' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1L7 7L13 1' stroke='white' stroke-width='2'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 16px center;
  background-size: 14px;
  cursor: pointer;
}

.field input:focus,
.field select:focus {
  border-color: #50b0ff;
}

</style>
<style>
.sr-section {
    text-align: center;
}

.sr-title {
    font-weight: 700;
    font-size: 2.5rem;
}

.sr-subtitle {
    color: #6c757d;
    max-width: 700px;
    margin: auto;
}

.sr-card {
    border: none;
    border-radius: 20px;
    padding: 30px;
    background: #ffffff;
    transition: all 0.3s ease;
    height: 100%;
    box-shadow: 0 10px 25px rgba(0,0,0,0.05);
}

.sr-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
}

.sr-icon-box {
    width: 70px;
    height: 70px;
    background: #0d6efd;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    margin-bottom: 20px;
}

.sr-img {
    width: 100%;
    border-radius: 15px;
    margin-bottom: 20px;
}

.sr-btn {
    padding: 12px 30px;
    font-weight: 600;
    border-radius: 30px;
}
</style>





<section class="flat-section-v3" style="padding:0px 0px 50px 0px;" id="appointment-schedule">

<!--div class="hero">
  <h2>Hello, Let’s Talk!</h2>
  <p style="color:white;">Schedule a one-to-one call to discuss your goals and challenges</p>
  <div class="alert">
    <span>!</span> This call is optional but highly recommended!
  </div>
</div-->

<div class="container mt-4">

<div class="row align-items-center" >

<!-- LEFT SIDE : CALENDAR -->
<div class="col-md-5" style="zoom:0.7;">

<h2>Choose a Date</h2>

<div class="calendar-card">

<div class="calendar-header mb-3">
<select id="month" class="form-control me-2"></select>
<select id="year" class="form-control"></select>
</div>

<div class="weekdays" aria-hidden="true">
<span>M</span><span>Tu</span><span>W</span>
<span>Th</span><span>Fri</span><span>Sa</span><span>Su</span>
</div>

<div id="calendar-days" class="days" role="grid" aria-label="Appointment calendar"></div>

</div>


<h2 class="mt-4">Pick a time</h2>

<div class="time-card">
<div id="times" class="times" role="listbox" aria-label="Available appointment times"></div>
<p id="times-status" class="times-status" aria-live="polite"></p>
</div>

<p class="timezone">All Times are in Eastern Time - US & Canada</p>

</div>


<!-- RIGHT SIDE : FORM -->
<div class="col-md-7 align-items-center" >

<div class="form-wrapper">
<h2 class="mt-4" style="font-size: 46px;">Personal Details</h2>
<form class="dark-form" id="appointmentForm">

<div class="field">
<label>Full Name *</label>
<input type="text" name="name" class="form-control" placeholder="John Doe" required>
</div>

<div class="field">
<label>E-mail Address *</label>
<input type="email" name="email" class="form-control" placeholder="your_email@example.com" required>
</div>

<div class="field">
<label>Phone Number *</label>
<input type="tel" name="phone" class="form-control" placeholder="+1 (___) ___-____" required>
</div>

<fieldset class="field consult-type-fieldset">
<legend id="consultation-type-legend">Consultation Type</legend>
<div class="consult-type-options" role="radiogroup" aria-required="true" aria-labelledby="consultation-type-legend">
@foreach(\App\Support\AppointmentScheduler::CONSULTATION_TYPES as $consultType)
<label>
<input type="radio" name="consultation_type" value="{{ $consultType }}" required>
<span>{{ $consultType }}</span>
</label>
@endforeach
</div>
</fieldset>

<p id="appointment-form-errors" class="appointment-error" role="alert" aria-live="assertive"></p>

<button type="submit" class="btn primary mt-3" id="appointment-submit">Book Appointment</button>

</form>

</div>

</div>

</div>
</div>

</section>

<section class="py-5 sr-section">
    <div class="container">

        <h2 class="sr-title mb-3">Why Choose Serik Realty</h2>
        <p class="sr-subtitle mb-5">
            At Serik Realty, we make up-sizing in the Toronto/GTA simple, seamless, and stress-free. 
            With personalized advice, local expertise, and a client-first approach, we help you find your next home with confidence.
        </p>

        <div class="row g-4">

            <!-- Card 1 -->
            <div class="col-md-4">
                <div class="sr-card">
                    <img src="images/expert-guidance.jpg" class="sr-img" alt="{{ __('Expert real estate guidance from Serik Realty') }}">
                    <div class="sr-icon-box mx-auto">
                        <i class="ti ti-users"></i>
                    </div>
                    <h5>Expert Guidance Every Step</h5>
                    <p>We help you sell your current home and find the perfect upgrade, making the entire process simple and stress-free.</p>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="col-md-4">
                <div class="sr-card">
                    <img src="images/custom-solutions.jpg" class="sr-img" alt="{{ __('Custom real estate solutions from Serik Realty') }}">
                    <div class="sr-icon-box mx-auto">
                        <i class="ti ti-home-heart"></i>
                    </div>
                    <h5>Solutions Tailored to You</h5>
                    <p>We focus on your lifestyle, priorities, and future growth to help you find a home that truly fits your needs.</p>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="col-md-4">
                <div class="sr-card">
                    <img src="images/market-insights.jpg" class="sr-img" alt="{{ __('Ontario real estate market insights from Serik Realty') }}">
                    <div class="sr-icon-box mx-auto">
                        <i class="ti ti-map-pin"></i>
                    </div>
                    <h5>Trusted Market Insights</h5>
                    <p>Our deep knowledge of Toronto/GTA neighborhoods helps you make confident and informed decisions.</p>
                </div>
            </div>

        </div>

        <!-- CTA -->
        <div class="mt-5">
            <a href="{{ url('/contact-us') }}" class="btn btn-primary sr-btn" style="color:#fff">See How We Work</a>
        </div>

    </div>
</section>


<script>
window.SERIK_APPOINTMENT = @json(\App\Support\AppointmentScheduler::frontendConfig());

(function () {
    const cfg = window.SERIK_APPOINTMENT || {};
    const calendarDays = document.getElementById("calendar-days");
    const monthSelect = document.getElementById("month");
    const yearSelect = document.getElementById("year");
    const timesContainer = document.getElementById("times");
    const timesStatus = document.getElementById("times-status");
    const form = document.getElementById("appointmentForm");
    const errorsEl = document.getElementById("appointment-form-errors");
    const submitBtn = document.getElementById("appointment-submit");
    if (!calendarDays || !monthSelect || !yearSelect || !timesContainer || !form) {
        return;
    }

    const months = [
        "January","February","March","April","May","June",
        "July","August","September","October","November","December"
    ];
    const todayStr = cfg.today;
    const minDate = cfg.minDate || todayStr;
    const maxDate = cfg.maxDate;
    const catalog = Array.isArray(cfg.catalog) ? cfg.catalog : [];

    let selectedDate = null;
    let selectedTime = null;
    let slotsAbort = null;
    let slotsSeq = 0;
    let submitting = false;
    let pollTimer = null;
    let pollAbort = null;

    const pendingMsg = cfg.pendingMessage || "We're confirming your appointment. Please wait.";
    const successMsg = cfg.successMessage || "Appointment booked successfully";
    const failureMsg = cfg.failureMessage || "We couldn't confirm your appointment. Please try again or contact Serik Realty.";
    const pollInterval = Number(cfg.pollIntervalMs) > 400 ? Number(cfg.pollIntervalMs) : 2000;
    const pollTimeout = Number(cfg.pollTimeoutMs) > 5000 ? Number(cfg.pollTimeoutMs) : 90000;
    const TOKEN_KEY = "serik_appointment_status_token";

    function pad(n) {
        return String(n).padStart(2, "0");
    }

    function ymd(year, monthIndex, day) {
        return year + "-" + pad(monthIndex + 1) + "-" + pad(day);
    }

    function parseYmd(value) {
        const parts = String(value || "").split("-");
        if (parts.length !== 3) return null;
        const year = Number(parts[0]);
        const month = Number(parts[1]);
        const day = Number(parts[2]);
        if (!year || !month || !day) return null;
        return { year: year, month: month, day: day };
    }

    function setStatus(text) {
        if (timesStatus) {
            timesStatus.textContent = text || "";
        }
    }

    function setError(text) {
        if (errorsEl) {
            errorsEl.textContent = text || "";
        }
    }

    function firstError(message) {
        if (typeof message === "string") return message;
        if (message && typeof message === "object") {
            const first = Object.values(message)[0];
            return Array.isArray(first) ? String(first[0] || "") : String(first || "");
        }
        return "Unable to book that appointment. Please try again.";
    }

    function setBusy(busy) {
        submitting = !!busy;
        if (submitBtn) {
            submitBtn.disabled = !!busy;
            if (busy) {
                submitBtn.setAttribute("aria-busy", "true");
            } else {
                submitBtn.removeAttribute("aria-busy");
            }
        }
    }

    function fireConfirmedAnalytics(reference) {
        try {
            if (window.dataLayer && Array.isArray(window.dataLayer)) {
                window.dataLayer.push({
                    event: "appointment_booked",
                    booking_reference: reference || ""
                });
            }
        } catch (err) {}
    }

    function resetFormAfterSuccess() {
        form.reset();
        selectedDate = null;
        selectedTime = null;
        renderCalendar();
        timesContainer.innerHTML = "";
        setStatus("Select a date to see available times.");
        setError("");
    }

    function onConfirmed(data) {
        stopPolling();
        try { sessionStorage.removeItem(TOKEN_KEY); } catch (err) {}
        fireConfirmedAnalytics(data && data.booking_reference);
        alert(successMsg);
        resetFormAfterSuccess();
        setBusy(false);
    }

    function onFailed(data) {
        stopPolling();
        try { sessionStorage.removeItem(TOKEN_KEY); } catch (err) {}
        const message = (data && data.message) ? firstError(data.message) : failureMsg;
        setError(message);
        alert(message);
        setBusy(false);
    }

    function stopPolling() {
        if (pollTimer) {
            clearTimeout(pollTimer);
            pollTimer = null;
        }
        if (pollAbort) {
            pollAbort.abort();
            pollAbort = null;
        }
    }

    function pollAppointment(token, started) {
        if (!token || !cfg.statusUrl) {
            onFailed({ message: failureMsg });
            return;
        }
        started = started || Date.now();
        if (Date.now() - started > pollTimeout) {
            onFailed({ message: failureMsg });
            return;
        }
        pollTimer = setTimeout(function () {
            if (pollAbort) {
                pollAbort.abort();
            }
            pollAbort = new AbortController();
            fetch(cfg.statusUrl + "?token=" + encodeURIComponent(token), {
                headers: { Accept: "application/json" },
                signal: pollAbort.signal
            })
                .then(function (res) { return res.json().then(function (data) { return { status: res.status, data: data || {} }; }); })
                .then(function (result) {
                    const data = result.data || {};
                    if (data.status === true || data.state === "confirmed") {
                        onConfirmed(data);
                        return;
                    }
                    if (data.state === "failed" || result.status === 410 || result.status === 404) {
                        onFailed(data);
                        return;
                    }
                    setError(data.message || pendingMsg);
                    pollAppointment(token, started);
                })
                .catch(function (err) {
                    if (err && err.name === "AbortError") return;
                    pollAppointment(token, started);
                });
        }, pollInterval);
    }

    const todayParts = parseYmd(todayStr) || { year: new Date().getFullYear(), month: 1, day: 1 };
    const maxParts = parseYmd(maxDate) || { year: 2030, month: 12, day: 31 };

    months.forEach(function (name, index) {
        monthSelect.insertAdjacentHTML("beforeend", '<option value="' + index + '">' + name + "</option>");
    });
    for (let y = todayParts.year; y <= maxParts.year; y++) {
        yearSelect.insertAdjacentHTML("beforeend", '<option value="' + y + '">' + y + "</option>");
    }
    monthSelect.value = String(todayParts.month - 1);
    yearSelect.value = String(todayParts.year);

    function isUnavailableDate(dateStr) {
        if (!dateStr) return true;
        if (minDate && dateStr < minDate) return true;
        if (maxDate && dateStr > maxDate) return true;
        if (dateStr === todayStr && cfg.todayHasSlots === false) return true;
        return false;
    }

    function selectDate(dateStr, dayEl) {
        if (isUnavailableDate(dateStr)) return;
        selectedDate = dateStr;
        selectedTime = null;
        calendarDays.querySelectorAll(".cal-day").forEach(function (el) {
            el.classList.remove("selected");
            el.setAttribute("aria-pressed", "false");
        });
        if (dayEl) {
            dayEl.classList.add("selected");
            dayEl.setAttribute("aria-pressed", "true");
        }
        loadTimes(dateStr);
    }

    function renderCalendar() {
        calendarDays.innerHTML = "";
        const month = Number(monthSelect.value);
        const year = Number(yearSelect.value);
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const start = firstDay === 0 ? 6 : firstDay - 1;

        for (let i = 0; i < start; i++) {
            calendarDays.appendChild(document.createElement("span"));
        }

        for (let d = 1; d <= daysInMonth; d++) {
            const dateStr = ymd(year, month, d);
            const unavailable = isUnavailableDate(dateStr);
            const isToday = dateStr === todayStr;
            const day = document.createElement("button");
            day.type = "button";
            day.className = "cal-day";
            day.textContent = String(d);
            day.dataset.date = dateStr;
            const labelParts = [months[month] + " " + d + ", " + year];
            if (isToday) {
                day.classList.add("is-today");
                day.setAttribute("aria-current", "date");
                const marker = document.createElement("span");
                marker.className = "cal-day-today-label";
                marker.textContent = "Today";
                day.appendChild(marker);
                labelParts.push("today");
            }
            if (unavailable) {
                day.classList.add("is-unavailable");
                day.disabled = true;
                day.setAttribute("aria-disabled", "true");
                day.tabIndex = -1;
                labelParts.push("unavailable");
            } else {
                day.setAttribute("aria-pressed", dateStr === selectedDate ? "true" : "false");
                day.addEventListener("click", function () {
                    selectDate(dateStr, day);
                });
            }
            if (dateStr === selectedDate && !unavailable) {
                day.classList.add("selected");
            }
            day.setAttribute("aria-label", labelParts.join(", "));
            calendarDays.appendChild(day);
        }
    }

    function renderTimes(slots) {
        timesContainer.innerHTML = "";
        selectedTime = null;
        const available = (slots || []).filter(function (slot) { return slot && slot.available; });
        if (!available.length) {
            setStatus("No remaining times for this date.");
            return;
        }
        setStatus("");
        available.forEach(function (slot) {
            const btn = document.createElement("button");
            btn.type = "button";
            btn.className = "time";
            btn.textContent = slot.label;
            btn.dataset.value = slot.value;
            btn.setAttribute("role", "option");
            btn.setAttribute("aria-label", slot.label);
            btn.setAttribute("aria-pressed", "false");
            btn.addEventListener("click", function () {
                timesContainer.querySelectorAll(".time").forEach(function (el) {
                    el.classList.remove("selected");
                    el.setAttribute("aria-pressed", "false");
                });
                btn.classList.add("selected");
                btn.setAttribute("aria-pressed", "true");
                selectedTime = slot.value;
                setError("");
            });
            timesContainer.appendChild(btn);
        });
    }

    function localSlotsForDate(dateStr) {
        return catalog.map(function (slot) {
            let available = true;
            if (isUnavailableDate(dateStr)) {
                available = false;
            } else if (dateStr === todayStr) {
                const nowMinutes = (Number(cfg.nowHour) * 60) + Number(cfg.nowMinute);
                available = ((slot.hour * 60) + slot.minute) > nowMinutes;
            }
            return { value: slot.value, label: slot.label, available: available };
        });
    }

    function loadTimes(dateStr) {
        const seq = ++slotsSeq;
        if (slotsAbort) {
            slotsAbort.abort();
        }
        timesContainer.innerHTML = "";
        setStatus("Loading available times…");
        const fallback = localSlotsForDate(dateStr);
        if (!cfg.slotsUrl) {
            renderTimes(fallback);
            return;
        }
        slotsAbort = new AbortController();
        fetch(cfg.slotsUrl + "?date=" + encodeURIComponent(dateStr), {
            headers: { Accept: "application/json" },
            signal: slotsAbort.signal
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (seq !== slotsSeq) return;
                if (data && data.status && Array.isArray(data.slots)) {
                    renderTimes(data.slots);
                    return;
                }
                renderTimes(fallback);
            })
            .catch(function (err) {
                if (err && err.name === "AbortError") return;
                if (seq !== slotsSeq) return;
                renderTimes(fallback);
            });
    }

    monthSelect.addEventListener("change", renderCalendar);
    yearSelect.addEventListener("change", renderCalendar);
    renderCalendar();

    const todayButton = calendarDays.querySelector('.cal-day.is-today:not([aria-disabled="true"])');
    if (todayButton && todayStr) {
        selectDate(todayStr, todayButton);
    } else {
        setStatus("Select a date to see available times.");
    }

    form.addEventListener("submit", function (e) {
        e.preventDefault();
        if (submitting) return;
        setError("");

        if (!selectedDate) {
            setError("Please select a date.");
            return;
        }
        if (!selectedTime) {
            setError("Please select a time.");
            return;
        }
        const typeInput = form.querySelector('input[name="consultation_type"]:checked');
        if (!typeInput) {
            setError("Please select a Consultation Type.");
            return;
        }

        submitting = true;
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.setAttribute("aria-busy", "true");
        }

        const formData = new FormData(form);
        formData.append("date", selectedDate);
        formData.append("time", selectedTime);

        fetch(cfg.bookUrl || "/api/v1/book-appointment", {
            method: "POST",
            headers: { Accept: "application/json" },
            body: formData
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, status: res.status, data: data };
                });
            })
            .then(function (result) {
                const data = result.data || {};
                if (data.status === true || data.state === "confirmed") {
                    onConfirmed(data);
                    return;
                }
                if (result.status === 202 || data.pending === true || data.state === "pending" || data.state === "processing") {
                    const token = data.token || "";
                    setError(data.message || pendingMsg);
                    if (token) {
                        try { sessionStorage.setItem(TOKEN_KEY, token); } catch (err) {}
                        pollAppointment(token);
                        return;
                    }
                    onFailed({ message: failureMsg });
                    return;
                }
                if (data.state === "failed") {
                    onFailed(data);
                    return;
                }
                if (Array.isArray(data.slots)) {
                    renderTimes(data.slots);
                }
                const message = firstError(data.message);
                setError(message);
                alert(typeof data.message === "string" ? data.message : message);
                setBusy(false);
            })
            .catch(function () {
                setError("Unable to book that appointment. Please try again.");
                setBusy(false);
            });
    });

    try {
        const resumeToken = sessionStorage.getItem(TOKEN_KEY);
        if (resumeToken && cfg.statusUrl) {
            setError(pendingMsg);
            setBusy(true);
            pollAppointment(resumeToken);
        }
    } catch (err) {}
})();
</script>