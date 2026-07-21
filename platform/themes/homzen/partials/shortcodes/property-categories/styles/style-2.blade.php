
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

.weekdays span {
  font-weight: 600;
  color: #666;
}

.days span {
  padding: 12px;
  cursor: pointer;
  border-radius: 50%;
}

.days span:hover {
  background: #eee;
}

.days .selected {
  background: #e82c2c;
  color: #fff;
}

.times {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 15px;
}

.time {
  padding: 12px;
  background: #f2f2f4;
  border-radius: 10px;
  text-align: center;
  cursor: pointer;
}

.time.selected {
  background: #e82c2c;
  color: #fff;
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

<div class="weekdays">
<span>M</span><span>Tu</span><span>W</span>
<span>Th</span><span>Fri</span><span>Sa</span><span>Su</span>
</div>

<div id="calendar-days" class="days"></div>

</div>


<h2 class="mt-4">Pick a time</h2>

<div class="time-card">
<div id="times" class="times"></div>
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

<div class="field">
<label>Property Address</label>
<input type="text" name="address" class="form-control" placeholder="Toronto, ON">
</div>

<div class="field">
<label>You are looking to?</label>
<select name="subject" class="form-control" required>
<option value="">Select option</option>
<option value="Sell your property">Sell your property</option>
<option value="Buy a property">Buy a property</option>
<option value="Rent">Rent</option>
</select>
</div>

<button type="submit" class="btn primary mt-3">Book Appointment</button>

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
let selectedDate = null;
let selectedTime = null;

/* ---------- CALENDAR ---------- */
const calendarDays = document.getElementById("calendar-days");
const monthSelect = document.getElementById("month");
const yearSelect = document.getElementById("year");

const months = [
"January","February","March","April","May","June",
"July","August","September","October","November","December"
];

months.forEach((m,i)=>{
monthSelect.innerHTML += `<option value="${i}">${m}</option>`;
});

for(let y=2021;y<=2030;y++){
yearSelect.innerHTML += `<option value="${y}">${y}</option>`;
}

monthSelect.value = new Date().getMonth();
yearSelect.value = new Date().getFullYear();

function renderCalendar(){

calendarDays.innerHTML = "";

const month = +monthSelect.value;
const year = +yearSelect.value;

const firstDay = new Date(year, month, 1).getDay();
const daysInMonth = new Date(year, month+1, 0).getDate();

const start = firstDay === 0 ? 6 : firstDay - 1;

for(let i=0;i<start;i++){
calendarDays.appendChild(document.createElement("span"));
}

for(let d=1; d<=daysInMonth; d++){

const day = document.createElement("span");
day.textContent = d;

day.onclick = () => {

document.querySelectorAll(".days span").forEach(x=>x.classList.remove("selected"));

day.classList.add("selected");

selectedDate = `${year}-${month+1}-${d}`;

};

calendarDays.appendChild(day);

}

}

monthSelect.onchange = renderCalendar;
yearSelect.onchange = renderCalendar;

renderCalendar();


/* ---------- TIME SLOTS ---------- */

const timesContainer = document.getElementById("times");

const times = ["9:30","10:30","11:30","1:30","2:30","3:30","4:30","5:30","6:30"];

times.forEach(t=>{

const div = document.createElement("div");

div.className = "time";

div.textContent = t;

div.onclick = () => {

document.querySelectorAll(".time").forEach(x=>x.classList.remove("selected"));

div.classList.add("selected");

selectedTime = t;

};

timesContainer.appendChild(div);

});



document.getElementById("appointmentForm").addEventListener("submit", function(e){

e.preventDefault();

if(!selectedDate){
alert("Please select date");
return;
}

if(!selectedTime){
alert("Please select time");
return;
}

let form = document.getElementById("appointmentForm");
let formData = new FormData(form);

formData.append("date", selectedDate);
formData.append("time", selectedTime);

fetch("/api/v1/book-appointment", {

method: "POST",

headers:{
'Accept':'application/json'
},

body: formData

})
.then(res => res.json())
.then(data => {

if(data.status === true){

alert("Appointment booked successfully");

form.reset();

}else{

alert(JSON.stringify(data.message));

}

})
.catch(error => console.log(error));

});

</script>