  <style>
    :root {
      --blue: #0b5ea8;
      --light-blue: #e6f2fb;
      --border: #dcdcdc;
      --text: #1f2937;
      --muted: #6b7280;
    }

   
    .container {
      
      padding: 0px;
     
      grid-template-columns: 3fr 1fr;
      gap: 24px;
    }

    h1 {
      margin-bottom: 8px;
    }

    .subtitle {
      color: var(--muted);
      margin-bottom: 24px;
    }

    /* Main Card */
    .calculator {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      overflow: hidden;
    }

    .calculator-header {
      background: var(--blue);
      color: #fff;
      padding: 16px;
      font-weight: 600;
      text-align: center;
    }

    .calculator-body {
      padding: 20px;
    }

    .row {
     
      grid-template-columns: 160px repeat(4, 1fr);
      align-items: center;
      margin-bottom: 14px;
    }

    .row label {
      font-size: 14px;
      font-weight: 600;
    }

    input, select {
      width: 100%;
      padding: 10px;
      border-radius: 6px;
      border: 1px solid var(--border);
      font-size: 14px;
    }

    .blue-row {
      background: var(--light-blue);
      padding: 12px;
      border-radius: 6px;
      font-weight: 600;
    }

    .blue-row div {
      text-align: center;
    }

    .rate-card {
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 12px;
      text-align: center;
      font-size: 14px;
    }

    .rate-card strong {
      font-size: 18px;
      display: block;
      margin-bottom: 4px;
    }

    .rate-card span {
      color: var(--muted);
      font-size: 12px;
    }

    .btn {
      margin-top: 8px;
      padding: 10px;
      background: var(--blue);
      color: #fff;
      border: none;
      width: 100%;
      border-radius: 6px;
      font-weight: 600;
      cursor: pointer;
    }

    .btn:hover {
      opacity: 0.9;
    }

    /* Sidebar */
    .sidebar {
      background: #fff;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      height: fit-content;
    }

    .sidebar h3 {
      margin-top: 0;
    }

    .rate {
      font-size: 24px;
      font-weight: 700;
      color: var(--blue);
    }

    .rate small {
      font-size: 14px;
      color: var(--muted);
      font-weight: 400;
    }

    .sidebar button {
      margin-top: 16px;
      width: 100%;
    }

    @media (max-width: 900px) {
      .container {
        grid-template-columns: 1fr;
      }

      .row {
        grid-template-columns: 1fr;
      }
    }
    
    
    
    
    
   /* Utilities */
.flex {
  display: flex;
  align-items: center;
}

.between {
  justify-content: space-between;
}

.muted {
  color: var(--muted);
  font-size: 14px;
  margin-bottom: 16px;
}

/* Cash needed to close */
.cash-body {
  display: grid;
  grid-template-columns: 1.2fr 1px 1.8fr;
  gap: 24px;
}

.cash-left,
.cash-right {
  padding: 8px 0;
}

.block-label {
  display: block;
  margin: 20px 0 8px;
  font-weight: 600;
  font-size: 14px;
}

.input {
  width: 100%;
  padding: 10px;
  border: 1px solid var(--border);
  border-radius: 6px;
  font-size: 14px;
}

.input.small {
  max-width: 140px;
}

.divider {
  background: var(--border);
}

/* Toggle buttons */
.btn-group {
  display: flex;
  gap: 8px;
}

.btn-toggle {
  background: #fff;
  color: var(--text);
  border: 1px solid var(--border);
}

.btn-toggle.active {
  background: #8ecaff;
  border-color: var(--blue);
  color: var(--blue);
}

/* Line items */
.line-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px dashed var(--border);
  font-size: 14px;
}

.line-item span:first-child {
  font-weight: 600;
}

/* Total */
.total {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 16px;
  font-size: 16px;
}

.total strong {
  color: var(--blue);
  font-size: 20px;
}

/* Chevron */
.chevron {
  font-size: 18px;
  cursor: pointer;
}

/* Responsive */
@media (max-width: 900px) {
  .cash-body {
    grid-template-columns: 1fr;
    gap:0px;
  }

  .divider {
    display: none;
  }
}

    .monthly-expenses .cash-left {
  padding-top: 4px;
}
   
   
  .option-btn {
      width: 80px;
    }

    .option-btn.active {
      background-color: #d0f0ff;
      border-color: #0b5ea8;
      color: #0b5ea8;
    }

    .divider {
      border-left: 1px solid #dee2e6;
      height: 100%;
    }

    .tax-row {
      display: flex;
      justify-content: space-between;
      padding: 6px 0;
      border-bottom: 1px dashed #dee2e6;
    }

    .tax-row:last-child {
      border-bottom: none;
      font-weight: bold;
    }  
    
    
    
    
    
    
    
  .mascot-box {
  position: fixed;
  bottom: 20px;
  right: 20px;
  width: 180px;
  z-index: 9999;
}

.mascot-box img {
  width: 100%;
  animation: floaty 1.2s infinite ease-in-out;
}

@keyframes floaty {
  0%   { transform: translateY(0px) scale(1); }
  50%  { transform: translateY(-8px) scale(1.03); }
  100% { transform: translateY(0px) scale(1); }
}

.hidden {
  display: none;
}
.mortgage-cal_sec{
    padding:50px 0px 0px 0px;
}
.mortgage-con{
    margin: 40px auto;
}
/* ===== MOBILE: SHOW ONLY FIRST COLUMN ===== */
@media (max-width: 768px) {
    

  .calculator-body .row {
    display: grid !important;
    grid-template-columns: 1fr !important;
    gap: 10px;
  }

  /* ✅ ONLY apply to rows that have multiple columns */
  .calculator-body .row.multi-col > *:not(label):not(:nth-child(2)) {
    display: none !important;
  }

  /* Full width inputs */
  .calculator-body input,
  .calculator-body select,
  .rate-card,
  .blue-row div {
    width: 100%;
    font-size: 16px;
  }
  .mortgage-cal_sec{
    padding:0px 0px 0px 0px;
}
.mortgage-con{
    margin: 0px auto;
    padding-left:10px;
     padding-right:10px;
}
}
.mortgage-layout{
  display: grid;
  grid-template-columns: 3fr 1fr;
  gap: 24px;
  align-items: start;
}

/* LEFT COLUMN STACK */
.mortgage-main{
  display: flex;
  flex-direction: column;
  gap: 24px;
  min-width: 0; /* IMPORTANT: prevents overflow cut */
}

/* RIGHT SIDEBAR */
.mortgage-sidebar{
  position: sticky;
  top: 80px;
  height: fit-content;
}

/* IMPORTANT FIX FOR CUT CONTENT */
.monthly-expenses,
.cash-close,
.calculator{
  width: 100%;
  min-width: 0;
  overflow: visible;
}
@media (max-width: 900px){
  .mortgage-layout{
    grid-template-columns: 1fr;
  }

  .mortgage-sidebar{
    position: relative;
    top: auto;
  }
}
.main-header {
    display: block !important;
    }
    
    
    .choices__input--cloned {
    min-width:100% !important;
    width: 100% !important;
}
    
  </style>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>


<section class="flat-section flat-latest-new-v2 mortgage-cal_sec" style="">
    <div class="container mortgage-con" style=" display: grid;max-width: 1200px;
       ">
       
       

    <!-- Main -->
    <div class="">
      
      <p class="subtitle">
        Easily estimate your mortgage payments, closing costs, and monthly carrying 
expenses using the Serik Realty Mortgage Payment Calculator.
      </p>
    <div class="mortgage-main">
        <div class="calculator">
            <div class="calculator-header">
              Mortgage calculator
            </div>
    
            <div class="calculator-body">
              <div class="row " style="">
                  <div class="col-md-6">
                      <label>Enter Amount</label>
                        <input data-amount value="" id="amount">
                  </div>
                  <div class="col-md-6">
                       <label>City</label>
                       
                        <select data-city id="mySelect" class="mySelect">
                            <option value="toronto">Toronto</option>
                            <option value="ottawa">Ottawa</option>
                            <option value="mississauga">Mississauga</option>
                            <option value="brampton">Brampton</option>
                            <option value="hamilton">Hamilton</option>
                            <option value="london">London</option>
                            <option value="markham">Markham</option>
                            <option value="vaughan">Vaughan</option>
                            <option value="kitchener">Kitchener</option>
                            <option value="windsor">Windsor</option>
                            <option value="richmond-hill">Richmond Hill</option>
                            <option value="oakville">Oakville</option>
                            <option value="burlington">Burlington</option>
                            <option value="sudbury">Sudbury</option>
                            <option value="oshawa">Oshawa</option>
                            <option value="barrie">Barrie</option>
                            <option value="st-catharines">St. Catharines</option>
                            <option value="cambridge">Cambridge</option>
                            <option value="kingston">Kingston</option>
                            <option value="guelph">Guelph</option>
                            <option value="thunder-bay">Thunder Bay</option>
                            <option value="ajax">Ajax</option>
                            <option value="waterloo">Waterloo</option>
                            <option value="pickering">Pickering</option>
                            <option value="whitby">Whitby</option>
                            <option value="niagara-falls">Niagara Falls</option>
                            <option value="brantford">Brantford</option>
                            <option value="milton">Milton</option>
                            <option value="peterborough">Peterborough</option>
                            <option value="sarnia">Sarnia</option>
                            <option value="loyalist">Loyalist</option>
                            <option value="clarington">Clarington</option>
                            <option value="halton-hills">Halton Hills</option>
                            <option value="caledon">Caledon</option>
                            <option value="aurora">Aurora</option>
                            <option value="newmarket">Newmarket</option>
                            <option value="tecumseh">Tecumseh</option>
                            <option value="lasalle">LaSalle</option>
                            <option value="amherstburg">Amherstburg</option>
                            <option value="leamington">Leamington</option>
                            <option value="chatham-kent">Chatham-Kent</option>
                            <option value="essex">Essex</option>
                            <option value="kingsville">Kingsville</option>
                            <option value="tillsonburg">Tillsonburg</option>
                            <option value="ingersoll">Ingersoll</option>
                            <option value="woodstock">Woodstock</option>
                            <option value="stratford">Stratford</option>
                            <option value="st-thomas">St. Thomas</option>
                            <option value="norfolk-county">Norfolk County</option>
                            <option value="haldimand-county">Haldimand County</option>
                            <option value="brant">Brant</option>
                            <option value="norwich">Norwich</option>
                            <option value="east-zorra-tavistock">East Zorra-Tavistock</option>
                            <option value="zorra">Zorra</option>
                            <option value="blandford-blenheim">Blandford-Blenheim</option>
                            <option value="south-west-oxford">South-West Oxford</option>
                            <option value="north-dumfries">North Dumfries</option>
                            <option value="woolwich">Woolwich</option>
                            <option value="wellesley">Wellesley</option>
                            <option value="perth-east">Perth East</option>
                            <option value="perth-south">Perth South</option>
                            <option value="north-perth">North Perth</option>
                            <option value="west-perth">West Perth</option>
                            <option value="st-marys">St. Marys</option>
                            <option value="huron-east">Huron East</option>
                            <option value="morris-turnberry">Morris-Turnberry</option>
                            <option value="bluewater">Bluewater</option>
                            <option value="central-huron">Central Huron</option>
                            <option value="south-huron">South Huron</option>
                            <option value="north-huron">North Huron</option>
                            <option value="goderich">Goderich</option>
                            <option value="kincardine">Kincardine</option>
                            <option value="south-bruce">South Bruce</option>
                            <option value="arran-elderslie">Arran-Elderslie</option>
                            <option value="brockton">Brockton</option>
                            <option value="hanover">Hanover</option>
                            <option value="west-grey">West Grey</option>
                            <option value="grey-highlands">Grey Highlands</option>
                            <option value="owen-sound">Owen Sound</option>
                            <option value="meaford">Meaford</option>
                            <option value="the-blue-mountains">The Blue Mountains</option>
                            <option value="collingwood">Collingwood</option>
                            <option value="wasaga-beach">Wasaga Beach</option>
                            <option value="clearview">Clearview</option>
                            <option value="springwater">Springwater</option>
                            <option value="oro-medonte">Oro-Medonte</option>
                            <option value="essa">Essa</option>
                            <option value="adjala-tosorontio">Adjala-Tosorontio</option>
                            <option value="new-tecumseth">New Tecumseth</option>
                            <option value="bradford-west-gwillimbury">Bradford West Gwillimbury</option>
                            <option value="east-gwillimbury">East Gwillimbury</option>
                            <option value="georgina">Georgina</option>
                            <option value="uxbridge">Uxbridge</option>
                            <option value="scugog">Scugog</option>
                            <option value="brock">Brock</option>
                            <option value="kawartha-lakes">Kawartha Lakes</option>
                            <option value="trent-lakes">Trent Lakes</option>
                            <option value="selwyn">Selwyn</option>
                            <option value="otonabee-south-monaghan">Otonabee-South Monaghan</option>
                            <option value="cavan-monaghan">Cavan Monaghan</option>
                            <option value="douro-dummer">Douro-Dummer</option>
                            <option value="havelock-belmont-methuen">Havelock-Belmont-Methuen</option>
                            <option value="asphodel-norwood">Asphodel-Norwood</option>
                            <option value="north-kawartha">North Kawartha</option>
                            <option value="alnwickhaldimand">Alnwick/Haldimand</option>
                            <option value="cramahe">Cramahe</option>
                            <option value="brighton">Brighton</option>
                            <option value="prince-edward-county">Prince Edward County</option>
                            <option value="greater-napanee">Greater Napanee</option>
                            <option value="lennox-and-addington">Lennox and Addington</option>
                            <option value="frontenac-islands">Frontenac Islands</option>
                            <option value="central-frontenac">Central Frontenac</option>
                            <option value="north-frontenac">North Frontenac</option>
                            <option value="south-frontenac">South Frontenac</option>
                            <option value="stone-mills">Stone Mills</option>
                            <option value="leeds-and-the-thousand-islands">Leeds and the Thousand Islands</option>
                            <option value="rideau-lakes">Rideau Lakes</option>
                            <option value="athens">Athens</option>
                            <option value="brockville">Brockville</option>
                            <option value="elizabethtown-kitley">Elizabethtown-Kitley</option>
                            <option value="front-of-yonge">Front of Yonge</option>
                            <option value="gananoque">Gananoque</option>
                            <option value="leeds-and-grenville">Leeds and Grenville</option>
                            <option value="augusta">Augusta</option>
                            <option value="edwardsburghcardinal">Edwardsburgh/Cardinal</option>
                            <option value="north-grenville">North Grenville</option>
                            <option value="prescott">Prescott</option>
                            <option value="south-dundas">South Dundas</option>
                            <option value="south-stormont">South Stormont</option>
                            <option value="south-glengarry">South Glengarry</option>
                            <option value="north-dundas">North Dundas</option>
                            <option value="north-stormont">North Stormont</option>
                            <option value="north-glengarry">North Glengarry</option>
                            <option value="cornwall">Cornwall</option>
                            <option value="russell">Russell</option>
                            <option value="the-nation">The Nation</option>
                            <option value="hawkesbury">Hawkesbury</option>
                            <option value="champlain">Champlain</option>
                            <option value="alfred-and-plantagenet">Alfred and Plantagenet</option>
                            <option value="clarence-rockland">Clarence-Rockland</option>
                            <option value="casselman">Casselman</option>
                            <option value="arnprior">Arnprior</option>
                            <option value="mississippi-mills">Mississippi Mills</option>
                            <option value="carleton-place">Carleton Place</option>
                            <option value="beckwith">Beckwith</option>
                            <option value="drummondnorth-elmsley">Drummond/North Elmsley</option>
                            <option value="lanark-highlands">Lanark Highlands</option>
                            <option value="montague">Montague</option>
                            <option value="perth">Perth</option>
                            <option value="smiths-falls">Smiths Falls</option>
                            <option value="tay-valley">Tay Valley</option>
                            <option value="addington-highlands">Addington Highlands</option>
                            <option value="loyalist-township">Loyalist Township</option>
                            <option value="tyendinaga">Tyendinaga</option>
                            <option value="deseronto">Deseronto</option>
                            <option value="quinte-west">Quinte West</option>
                            <option value="belleville">Belleville</option>
                            <option value="prince-edward">Prince Edward</option>
                            <option value="stirling-rawdon">Stirling-Rawdon</option>
                            <option value="centre-hastings">Centre Hastings</option>
                            <option value="tweed">Tweed</option>
                            <option value="madoc">Madoc</option>
                            <option value="marmora-and-lake">Marmora and Lake</option>
                            <option value="hastings-highlands">Hastings Highlands</option>
                            <option value="bancroft">Bancroft</option>
                            <option value="lanark">Lanark</option>
                            <option value="renfrew">Renfrew</option>
                            <option value="pembroke">Pembroke</option>
                            <option value="petawawa">Petawawa</option>
                            <option value="deep-river">Deep River</option>
                            <option value="laurentian-hills">Laurentian Hills</option>
                            <option value="killaloe-hagarty-and-richards">Killaloe, Hagarty and Richards</option>
                            <option value="whitewater-region">Whitewater Region</option>
                            <option value="admastonbromley">Admaston/Bromley</option>
                            <option value="bonnechere-valley">Bonnechere Valley</option>
                            <option value="brudenell-lyndoch-and-raglan">Brudenell, Lyndoch and Raglan</option>
                            <option value="greater-madawaska">Greater Madawaska</option>
                            <option value="horton">Horton</option>
                            <option value="madawaska-valley">Madawaska Valley</option>
                            <option value="mcnabbraeside">McNab/Braeside</option>
                            <option value="north-algona-wilberforce">North Algona Wilberforce</option>
                            <option value="head-clara-and-maria">Head, Clara and Maria</option>
                            <option value="laurentian-valley">Laurentian Valley</option>
                            <option value="killaloe">Killaloe</option>
                            <option value="whitestone">Whitestone</option>
                            <option value="seguin">Seguin</option>
                            <option value="mckellar">McKellar</option>
                            <option value="carling">Carling</option>
                            <option value="the-archipelago">The Archipelago</option>
                            <option value="perry">Perry</option>
                            <option value="ryerson">Ryerson</option>
                            <option value="armour">Armour</option>
                            <option value="burks-falls">Burk's Falls</option>
                            <option value="magnetawan">Magnetawan</option>
                            <option value="parry-sound">Parry Sound</option>
                            <option value="mcdougall">McDougall</option>
                            <option value="nipissing">Nipissing</option>
                            <option value="callander">Callander</option>
                            <option value="chisholm">Chisholm</option>
                            <option value="powassan">Powassan</option>
                            <option value="east-ferris">East Ferris</option>
                            <option value="west-nipissing">West Nipissing</option>
                            <option value="mattawa">Mattawa</option>
                            <option value="temagami">Temagami</option>
                            <option value="bonfield">Bonfield</option>
                            <option value="strong">Strong</option>
                            <option value="joly">Joly</option>
                            <option value="kearney">Kearney</option>
                            <option value="sundridge">Sundridge</option>
                            <option value="south-river">South River</option>
                            <option value="machar">Machar</option>
                            <option value="bracebridge">Bracebridge</option>
                            <option value="gravenhurst">Gravenhurst</option>
                            <option value="huntsville">Huntsville</option>
                            <option value="lake-of-bays">Lake of Bays</option>
                            <option value="georgian-bay">Georgian Bay</option>
                            <option value="severn">Severn</option>
                            <option value="ramara">Ramara</option>
                            <option value="tay">Tay</option>
                            <option value="midland">Midland</option>
                            <option value="penetanguishene">Penetanguishene</option>
                            <option value="tiny">Tiny</option>
                            <option value="huron-kinloss">Huron-Kinloss</option>
                            <option value="ashfield-colborne-wawanosh">Ashfield-Colborne-Wawanosh</option>
                            <option value="lambton-shores">Lambton Shores</option>
                            <option value="st-clair">St. Clair</option>
                            <option value="plympton-wyoming">Plympton-Wyoming</option>
                            <option value="warwick">Warwick</option>
                            <option value="point-edward">Point Edward</option>
                            <option value="oil-springs">Oil Springs</option>
                            <option value="enniskillen">Enniskillen</option>
                            <option value="brooke-alvinston">Brooke-Alvinston</option>
                            <option value="dawn-euphemia">Dawn-Euphemia</option>
                            <option value="strathroy-caradoc">Strathroy-Caradoc</option>
                            <option value="middlesex-centre">Middlesex Centre</option>
                            <option value="lucan-biddulph">Lucan Biddulph</option>
                            <option value="north-middlesex">North Middlesex</option>
                            <option value="southwest-middlesex">Southwest Middlesex</option>
                            <option value="adelaide-metcalfe">Adelaide Metcalfe</option>
                            <option value="newbury">Newbury</option>
                            <option value="thames-centre">Thames Centre</option>
                            <option value="central-elgin">Central Elgin</option>
                            <option value="southwold">Southwold</option>
                            <option value="duttondunwich">Dutton/Dunwich</option>
                            <option value="west-elgin">West Elgin</option>
                            <option value="aylmer">Aylmer</option>
                            <option value="bayham">Bayham</option>
                            <option value="malahide">Malahide</option>
                            <option value="welland">Welland</option>
                            <option value="port-colborne">Port Colborne</option>
                            <option value="thorold">Thorold</option>
                            <option value="niagara-on-the-lake">Niagara-on-the-Lake</option>
                            <option value="fort-erie">Fort Erie</option>
                            <option value="grimsby">Grimsby</option>
                            <option value="lincoln">Lincoln</option>
                            <option value="west-lincoln">West Lincoln</option>
                            <option value="pelham">Pelham</option>
                            <option value="wainfleet">Wainfleet</option>
    
                          </select>
                  </div>
                
                  
              </div>
              <!-- Price -->
              <div class="row multi-col" style=" display: grid;gap: 12px;">
                <label>Down Payment Percentage</label>
                  <input data-percentage value="5">
                  <input data-percentage value="10">
                  <input data-percentage value="15">
                  <input data-percentage value="20">
              </div>
    
              <!-- Down Payment -->
              <div class="row multi-col" style=" display: grid;gap: 12px;">
                <label>Down payment</label>
                  <input data-down>
                  <input data-down>
                  <input data-down>
                  <input data-down>
              </div>
    
              <!-- CMHC -->
              <div class="row multi-col" style=" display: grid;gap: 12px;">
                <label>CMHC insurance</label>
                <div data-cmhc></div>
                  <div data-cmhc></div>
                  <div data-cmhc></div>
                  <div data-cmhc></div>
              </div>
    
              <!-- Total Mortgage -->
              <div class="row blue-row multi-col" style=" display: grid;gap: 12px;">
                <label>Total mortgage</label>
                <div data-total></div>
                  <div data-total></div>
                  <div data-total></div>
                  <div data-total></div>
              </div>
    
              <!-- Amortization -->
              <div class="row multi-col" style=" display: grid;gap: 12px;">
                <label>Amortization</label>
                  <select data-years>
                      <option value="1">1-year</option>
                      <option value="2">2-year</option>
                      <option value="3">3-year</option>
                      <option value="4">4-year</option>
                      <option value="5">5-year</option>
                      <option value="6">6-year</option>
                      <option value="7">7-year</option>
                      <option value="8">8-year</option>
                      <option value="9">9-year</option>
                      <option value="10">10-year</option>
                      <option value="11">11-year</option>
                      <option value="12">12-year</option>
                      <option value="13">13-year</option>
                      <option value="14">14-year</option>
                      <option value="15">15-year</option>
                      <option value="16">16-year</option>
                      <option value="17">17-year</option>
                      <option value="18">18-year</option>
                      <option value="19">19-year</option>
                      <option value="20">20-year</option>
                      <option value="21">21-year</option>
                      <option value="22">22-year</option>
                      <option value="23">23-year</option>
                      <option value="24">24-year</option>
                      <option value="25" selected>25-year</option>
                      <option value="26">26-year</option>
                      <option value="27">27-year</option>
                      <option value="28">28-year</option>
                      <option value="29">29-year</option>
                      <option value="30">30-year</option>
                  </select>
                  <select data-years>
                      <option value="1">1-year</option>
                      <option value="2">2-year</option>
                      <option value="3">3-year</option>
                      <option value="4">4-year</option>
                      <option value="5">5-year</option>
                      <option value="6">6-year</option>
                      <option value="7">7-year</option>
                      <option value="8">8-year</option>
                      <option value="9">9-year</option>
                      <option value="10">10-year</option>
                      <option value="11">11-year</option>
                      <option value="12">12-year</option>
                      <option value="13">13-year</option>
                      <option value="14">14-year</option>
                      <option value="15">15-year</option>
                      <option value="16">16-year</option>
                      <option value="17">17-year</option>
                      <option value="18">18-year</option>
                      <option value="19">19-year</option>
                      <option value="20">20-year</option>
                      <option value="21">21-year</option>
                      <option value="22">22-year</option>
                      <option value="23">23-year</option>
                      <option value="24">24-year</option>
                      <option value="25" selected>25-year</option>
                      <option value="26">26-year</option>
                      <option value="27">27-year</option>
                      <option value="28">28-year</option>
                      <option value="29">29-year</option>
                      <option value="30">30-year</option>
                  </select>
                  <select data-years>
                      <option value="1">1-year</option>
                      <option value="2">2-year</option>
                      <option value="3">3-year</option>
                      <option value="4">4-year</option>
                      <option value="5">5-year</option>
                      <option value="6">6-year</option>
                      <option value="7">7-year</option>
                      <option value="8">8-year</option>
                      <option value="9">9-year</option>
                      <option value="10">10-year</option>
                      <option value="11">11-year</option>
                      <option value="12">12-year</option>
                      <option value="13">13-year</option>
                      <option value="14">14-year</option>
                      <option value="15">15-year</option>
                      <option value="16">16-year</option>
                      <option value="17">17-year</option>
                      <option value="18">18-year</option>
                      <option value="19">19-year</option>
                      <option value="20">20-year</option>
                      <option value="21">21-year</option>
                      <option value="22">22-year</option>
                      <option value="23">23-year</option>
                      <option value="24">24-year</option>
                      <option value="25" selected>25-year</option>
                      <option value="26">26-year</option>
                      <option value="27">27-year</option>
                      <option value="28">28-year</option>
                      <option value="29">29-year</option>
                      <option value="30">30-year</option>
                  </select>
                  <select data-years>
                      <option value="1">1-year</option>
                      <option value="2">2-year</option>
                      <option value="3">3-year</option>
                      <option value="4">4-year</option>
                      <option value="5">5-year</option>
                      <option value="6">6-year</option>
                      <option value="7">7-year</option>
                      <option value="8">8-year</option>
                      <option value="9">9-year</option>
                      <option value="10">10-year</option>
                      <option value="11">11-year</option>
                      <option value="12">12-year</option>
                      <option value="13">13-year</option>
                      <option value="14">14-year</option>
                      <option value="15">15-year</option>
                      <option value="16">16-year</option>
                      <option value="17">17-year</option>
                      <option value="18">18-year</option>
                      <option value="19">19-year</option>
                      <option value="20">20-year</option>
                      <option value="21">21-year</option>
                      <option value="22">22-year</option>
                      <option value="23">23-year</option>
                      <option value="24">24-year</option>
                      <option value="25" selected>25-year</option>
                      <option value="26">26-year</option>
                      <option value="27">27-year</option>
                      <option value="28">28-year</option>
                      <option value="29">29-year</option>
                      <option value="30">30-year</option>
                  </select>
              </div>
    
              <!-- Rate -->
              <div class="row multi-col" style=" display: grid;gap: 12px;">
                <label>Mortgage rate</label>
                    <div class="rate-card">
                      <input type="text" data-rate value="4.00" > <span style="text-align: left;font-size: 12px;  color: gray;">Rate (%)</span>
                    </div>
                    
                    <div class="rate-card">
                      <input type="text" data-rate value="4.00" > <span style="text-align: left;font-size: 12px;  color: gray;">Rate (%)</span>
                    </div>
                    
                    <div class="rate-card">
                      <input type="text" data-rate value="4.00" > <span style="text-align: left;font-size: 12px;  color: gray;">Rate (%)</span>
                    </div>
                    
                    <div class="rate-card">
                      <input type="text" data-rate value="4.00" > <span style="text-align: left;font-size: 12px;  color: gray;">Rate (%)</span>
                    </div>
              </div>
              
              
              <div class="row multi-col" style=" display: grid;gap: 12px;">
                  <label>Payment Frequency</label>
                <select name="paymentFrequency">
                  <option value="weekly">Weekly</option>
                  <option value="bi-weekly">Bi-Weekly</option>
                  <option value="semi-monthly">Semi-Monthly</option>
                  <option value="monthly" selected>Monthly</option>
                  <option value="quarterly">Quarterly</option>
                  <option value="semi-annually">Semi-Annually</option>
                  <option value="annually">Annually (Yearly)</option>
                </select>
                
                <select name="paymentFrequency">
                  <option value="weekly">Weekly</option>
                  <option value="bi-weekly">Bi-Weekly</option>
                  <option value="semi-monthly">Semi-Monthly</option>
                  <option value="monthly" selected>Monthly</option>
                  <option value="quarterly">Quarterly</option>
                  <option value="semi-annually">Semi-Annually</option>
                  <option value="annually">Annually (Yearly)</option>
                </select>
                
                <select name="paymentFrequency">
                  <option value="weekly">Weekly</option>
                  <option value="bi-weekly">Bi-Weekly</option>
                  <option value="semi-monthly">Semi-Monthly</option>
                  <option value="monthly" selected>Monthly</option>
                  <option value="quarterly">Quarterly</option>
                  <option value="semi-annually">Semi-Annually</option>
                  <option value="annually">Annually (Yearly)</option>
                </select>
                
                <select name="paymentFrequency">
                  <option value="weekly">Weekly</option>
                  <option value="bi-weekly">Bi-Weekly</option>
                  <option value="semi-monthly">Semi-Monthly</option>
                  <option value="monthly" selected>Monthly</option>
                  <option value="quarterly">Quarterly</option>
                  <option value="semi-annually">Semi-Annually</option>
                  <option value="annually">Annually (Yearly)</option>
                </select>
              </div>
              
    
              <!-- Payment -->
              <div class="row blue-row multi-col" style=" display: grid;gap: 12px;">
                <label>Mortgage payment</label>
                  <div data-payment></div>
                  <div data-payment></div>
                  <div data-payment></div>
                  <div data-payment></div>
              </div>
    
              <!-- Buttons >
              <div class="row" style=" display: grid;gap: 12px;">
                <label></label>
                <button class="btn">get this rate</button>
                <button class="btn">get this rate</button>
                <button class="btn">get this rate</button>
                <button class="btn">get this rate</button>
              </div-->
    
            </div>
        </div>
      
      
        <div class="container">
        <div class="row border p-4 rounded shadow-sm align-items-center">
          <!-- Left side: Question -->
          <div class="col-md-4">
            <label class="form-label fw-bold d-block mb-3">Are you a first time home buyer?</label>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-outline-secondary option-btn" id="yesBtn">Yes</button>
              <button type="button" class="btn btn-outline-secondary option-btn active" id="noBtn">No</button>
            </div>
          </div>
    
          <!-- Divider -->
          <div class="col-md-1 d-flex justify-content-center">
            <div class="divider"></div>
          </div>
    
          <!-- Right side: Tax details -->
          <div class="col-md-7">
            <div class="tax-row">
              <span>Provincial</span>
              <span>$725</span>
            </div>
            <div class="tax-row">
              <span>Municipal</span>
              <span>$725</span>
            </div>
            <div class="tax-row">
              <span>Rebate</span>
              <span>$0</span>
            </div>
            <div class="tax-row mt-2">
              <span>Land transfer tax</span>
              <span>$1,450</span>
            </div>
          </div>
        </div>
      </div>
      
      
        <div class="calculator cash-close">
        
        
          <!-- Header -->
          <div class="calculator-header flex between">
            <span>Cash needed to close</span>
            <span class="chevron">⌃</span>
          </div>
        
          <div class="calculator-body cash-body">
        
            <!-- Left column -->
            <div class="cash-left">
              <p class="muted">
                When you purchase a house, there are a number of costs you will need to
                put aside in addition to your down payment.
              </p>
        
              <label class="block-label">Down payment options</label>
              <select id="downPercent1">
                  <option value="5">5%</option>
                  <option value="10">10%</option>
                  <option value="15">15%</option>
                  <option value="20">20%</option>
                </select>
        
              <!--label class="block-label">Type of house</label>
              <div class="btn-group">
                <button class="btn btn-toggle active">House</button>
                <button class="btn btn-toggle">Condo</button>
              </div-->
              
                <label class="block-label"> <img src="https://serik.ca/storage/mortgage-banner-1.webp" style="width:90%"/></label>
                <div class="btn-group">
                  <button id="cashbackYes" class="btn btn-toggle active">With Serik</button>
                  <button id="cashbackNo" class="btn btn-toggle ">Without Serik </button>
                  <br>
                 
                </div>
                <div id="mascotBox" class="mascot-box hidden">
                  <img src="https://serik.ca/storage/untitled-ezgifcom-optimize-1.gif" alt="Mascot" />
                </div>
                <div id="serikTrigger"></div>
                 <span style="color:red;">*Some Terms and Conditions Apply</span>
                 
            </div>
        
            <!-- Divider -->
            <div class="divider"></div>
        
            <!-- Right column -->
            <div class="cash-right">
              <div class="line-item">
                <span>Down payment</span>
                <span>$5,000</span>
              </div>
        
              <div class="line-item">
                <span>Land transfer tax</span>
                <span>$0</span>
              </div>
        
              <!--div class="line-item">
                <span>PST on mortgage insurance</span>
                <span>$0</span>
              </div-->
        
              <div class="line-item">
                <span>Lawyer fees</span>
                <input class="input small" />
              </div>
        
              <div class="line-item">
                <span>Title insurance</span>
                <input class="input small" />
              </div>
        
              <div class="line-item">
                <span>Home inspection</span>
                <input class="input small" />
              </div>
        
              <div class="line-item">
                <span>Appraisal fees</span>
                <input class="input small" />
              </div>
              <div class="line-item">
                  <span>Buy Home With Serik and Get Upto 1.5% Cashback <br> <span style="color:red;">*Some Terms and Conditions Apply</span></span>
                  <span id="cashback-amount" style="    font-size: 20px;
            font-weight: 700;
            color: #e3050a;">$0</span>
                </div>
        
              <div class="total">
                <span>Cash needed to close</span>
                <strong id="cash-close-total" >$0</strong>
              </div>
            </div>
        
          </div>
        </div>
        
        
        <div class="calculator monthly-expenses">
        
          <!-- Header -->
          <div class="calculator-header flex between">
            <span>Monthly expenses</span>
            <span class="chevron">⌃</span>
          </div>
        
          <div class="calculator-body cash-body">
        
            <!-- Left column -->
            <div class="cash-left">
              <label class="block-label">Down payment options</label>
              <select id="downPercent">
                  <option value="5">5%</option>
                  <option value="7.5">7.5%</option>
                  <option value="8.5">8.5%</option>
                  <option value="10">10%</option>
                  <option value="15">15%</option>
                  <option value="20">20%</option>
                </select>
        
              <label class="block-label">Type of house</label>
              <div class="btn-group">
                <button class="btn btn-toggle active">Condo</button>
                <button class="btn btn-toggle">Freehold</button>
              </div>
            </div>
        
            <!-- Divider -->
            <div class="divider"></div>
        
            <!-- Right column -->
            <div class="cash-right">
        
              <div class="line-item">
                  <span>Mortgage payment</span>
                  <span id="mortgage-payment" data-payment="0">$0</span>
                </div>
        
              <div class="line-item">
                  <span>Property tax</span>
                  <input class="input small" value="$833" />
                </div>
                 <div class="line-item">
                  <span>Maintenance Fee</span>
                  <input class="input small" value="$150" />
                </div>
                
                <div class="line-item">
                  <span>Utilities</span>
                  <input class="input small" value="$185" />
                </div>
                
                <div class="line-item">
                  <span>Property insurance</span>
                  <input class="input small" value="$50" />
                </div>
                
                <div class="line-item">
                  <span>Phone</span>
                  <input class="input small" value="$50" />
                </div>
                
                <div class="line-item">
                  <span>Cable</span>
                  <input class="input small" value="$50" />
                </div>
                
                <div class="line-item">
                  <span>Internet</span>
                  <input class="input small" value="$50" />
                </div>
               
        
              <div class="total">
                <span>Monthly expenses</span>
                <strong id="monthly-expenses-total">$0</strong>
              </div>
        
            </div>
          </div>
        </div>


      
     </div> 
      
      
</div>
<div class="mortgage-sidebar">
    <div class="sidebar">
        <h3>Today’s best mortgage rates</h3>

        <p class="rate" id="rate-fixed">Loading... <small>5-yr fixed</small></p>
        <p class="rate" id="rate-variable">Loading... <small>5-yr variable</small></p>

        <button class="btn">see which rates I qualify for</button>
    </div>
</div> 
    
    
    

       
       
    </div>
</section>





<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>

<script>
async function loadMortgageRates() {
  try {
    const res = await fetch(
      "https://www.bankofcanada.ca/valet/observations/CMNIMIDAYS?format=json"
    );

    const data = await res.json();

    // get latest observation
    const latest = data.observations.at(-1);

    // value from API
    const rate = parseFloat(latest.CMNIMIDAYS.v).toFixed(2);

    // inject into UI
    document.getElementById("rate-fixed").innerHTML =
      rate + "% <small>5-yr fixed (BoC)</small>";

    document.getElementById("rate-variable").innerHTML =
      (rate - 0.5).toFixed(2) + "% <small>5-yr variable (est.)</small>";

  } catch (err) {
    console.error("Failed to load rates:", err);

    document.getElementById("rate-fixed").innerHTML =
      "4.00% <small>5-yr fixed</small>";

    document.getElementById("rate-variable").innerHTML =
      "3.50% <small>5-yr variable</small>";
  }
}

// run on page load
loadMortgageRates();
</script>




<script>
document.addEventListener("DOMContentLoaded", function () {
    const element = document.getElementById('mySelect');

    if (element) {
        new Choices(element, {
            searchEnabled: true,
            itemSelectText: '',
            shouldSort: false
        });
    }
});
</script>




<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

<script>
const yesBtn = document.getElementById("cashbackYes");
const noBtn = document.getElementById("cashbackNo");
const mascot = document.getElementById("mascotBox");
const trigger = document.getElementById("serikTrigger");

let confettiInterval = null;
let hideTimeout = null;

function hasValidAmount() {
  const input12 = document.getElementById("amount");
  if (!input12) return false;

  const value12 = input12.value.trim();

  return value12 !== "";
}

function startCelebration() {
    
    //  if (!hasValidAmount()) return;

  stopCelebration();

  // show instantly (no delay)
  mascot.classList.remove("hidden");

  // confetti loop only
  confettiInterval = setInterval(() => {
    confetti({
      particleCount: 60,
      spread: 80,
      origin: { y: 0.6 },
      colors: ['#4f46e5', '#22c55e', '#f59e0b', '#ef4444']
    });
  }, 700);

  hideTimeout = setTimeout(() => {
    stopCelebration();
  }, 10000);
}



function stopCelebration() {
  if (confettiInterval) clearInterval(confettiInterval);
  if (hideTimeout) clearTimeout(hideTimeout);

  confettiInterval = null;
  hideTimeout = null;

  // hide instantly (no animation delay)
  mascot.classList.add("hidden");
}

// BUTTON CLICK
yesBtn.addEventListener("click", function () {

  yesBtn.classList.add("active");
  noBtn.classList.remove("active");

  // ALWAYS re-trigger on click
  startCelebration();
});

noBtn.addEventListener("click", function () {

  noBtn.classList.add("active");
  yesBtn.classList.remove("active");

  stopCelebration();
});

// SCROLL TRIGGER
const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {

    if (entry.isIntersecting) {

      // ✅ check BOTH conditions
      if (
        yesBtn.classList.contains("active") &&
        hasValidAmount()
      ) {
        startCelebration();
      }

    }
  });
}, {
  threshold: 0.5
});

observer.observe(trigger);
</script>




<script>





document.addEventListener("DOMContentLoaded", () => {

  const $$ = (s) => Array.from(document.querySelectorAll(s));
  const $ = (s) => document.querySelector(s);

  let isEditingDown = false;

  const amountInput = $("[data-amount]");
  const downPercentInputs = $$("[data-percentage]");
  const downInputs = $$("[data-down]");
  const cmhcFields = $$("[data-cmhc]");
  const totalFields = $$("[data-total]");
  const paymentFields = $$("[data-payment]");
  const rateFields = $$("[data-rate]");
  const amortizationSelects = $$("[data-years]");
  const frequencySelects = $$("[name='paymentFrequency']");
  let userChangedDownSelect = false;
  
  


  // ===== HELPERS =====
  function formatMoney(val) {
      return "$" + Number(val.toFixed(2)).toLocaleString("en-US", {
        maximumFractionDigits: 0
      });
  }

  function formatPercent(val) {
    return Number(val).toLocaleString("en-US", {
      maximumFractionDigits: 2
    }) + "%";
  }

  function parseNumber(val) {
    return parseFloat(String(val).replace(/[^0-9.-]+/g, "")) || 0;
  }

  function getMinDownPayment(P) {
    if (P <= 500000) return 0.05 * P;
    if (P <= 1500000) return (0.05 * 500000) + (0.10 * (P - 500000));
    return 0.20 * P;
  }

  function getCMHCRate(dpPercent) {
    if (dpPercent < 10) return 0.04;
    if (dpPercent < 15) return 0.031;
    if (dpPercent < 20) return 0.028;
    return 0;
  }

  function calculatePayment(M, r, years, freq) {
    let paymentsPerYear = 12;
    switch(freq) {
      case "weekly": paymentsPerYear = 52; break;
      case "bi-weekly": paymentsPerYear = 26; break;
      case "semi-monthly": paymentsPerYear = 24; break;
      case "monthly": paymentsPerYear = 12; break;
      case "quarterly": paymentsPerYear = 4; break;
      case "semi-annually": paymentsPerYear = 2; break;
      case "annually": paymentsPerYear = 1; break;
    }

    const ratePerPeriod = Math.pow(1 + (r / 100) / 2, 2 / paymentsPerYear) - 1;
    const N = years * paymentsPerYear;

    if (ratePerPeriod === 0) return M / N;

    return M * (
      (ratePerPeriod * Math.pow(1 + ratePerPeriod, N)) /
      (Math.pow(1 + ratePerPeriod, N) - 1)
    );
  }
  
  downPercentInputs.forEach(input => {
  input.dataset.original = input.value;
});

  // ===== MAIN CALC =====
  function calculateAll() {
    const P = parseNumber(amountInput.value);
    if (!P) return;

    const minDP = getMinDownPayment(P);

    downPercentInputs.forEach((dpInput, i) => {

      let originalPercent = parseNumber(dpInput.dataset.original || dpInput.value);
        let dpPercent = originalPercent;
        let dpAmount = (dpPercent / 100) * P;

      // Enforce minimum DP
    if (dpAmount < minDP) {
      dpAmount = minDP;
      dpPercent = (dpAmount / P) * 100;
    
      dpInput.value = formatPercent(dpPercent);
        } else {
          // restore original when no minimum rule is applied
          dpInput.value = formatPercent(originalPercent);
        }

      // Update $ field only if NOT editing
      if (!isEditingDown) {
        downInputs[i].value = formatMoney(dpAmount);
      }

      // ✅ Correct loan calculation including CMHC for <20% DP
      let loan = P - dpAmount;
      let cmhc = 0;
      const rateCMHC = getCMHCRate(dpPercent);
      if (dpPercent < 20) {
        cmhc = loan * rateCMHC;
        loan += cmhc; // add insurance to principal
      }

      const totalMortgage = loan;

      const rate = parseNumber(rateFields[i].value);
      const years = parseInt(amortizationSelects[i].value);
      const freq = frequencySelects[i].value;

      const finalPayment = calculatePayment(totalMortgage, rate, years, freq);

      cmhcFields[i].innerText = formatMoney(cmhc);
      totalFields[i].innerText = formatMoney(totalMortgage);
      paymentFields[i].innerText = formatMoney(finalPayment);

      const downSelect = document.getElementById("downPercent");
      if (downSelect && parseNumber(downPercentInputs[i].value) === parseNumber(downSelect.value)) {
        const mortgageEl = document.getElementById("mortgage-payment");
        if (mortgageEl) {
          mortgageEl.innerText = formatMoney(finalPayment);
          mortgageEl.dataset.payment = finalPayment;
        }
      }
      
      console.log({
      price: P,
      downPayment: dpAmount,
      loanBeforeInsurance: P - dpAmount,
      cmhc: 99,
      totalMortgage: totalMortgage,
      rate: rate,
      years: years,
      payment: finalPayment
    });

    });
     

    if (window.calculateMonthlyExpenses) {
      window.calculateMonthlyExpenses();
    }
    
        const downSelect = document.getElementById("downPercent");
        
          downSelect?.addEventListener("change", () => {
              userChangedDownSelect = true;
            });

        if (downSelect && downPercentInputs.length > 0) {
        
          const firstPercent = parseNumber(downPercentInputs[0].value);
        
          const optionExists = Array.from(downSelect.options).some(
            opt => parseFloat(opt.value) === firstPercent
          );
        
          // 👉 ONLY auto-set if user has NOT touched dropdown
          if (!userChangedDownSelect && optionExists) {
            downSelect.value = firstPercent.toString();
          }
        }
  }

  // ===== EVENTS =====
  amountInput.addEventListener("focus", (e) => { e.target.value = parseNumber(e.target.value) || ""; });
  amountInput.addEventListener("input", () => { calculateAll(); });
  amountInput.addEventListener("blur", (e) => { 
    let val = parseNumber(e.target.value);
    e.target.value = val ? formatMoney(val) : "";
    calculateAll();
  });

  downPercentInputs.forEach((input) => {
    input.addEventListener("focus", () => { input.value = parseNumber(input.value) || ""; });
    input.addEventListener("blur", () => { 
      let val = parseNumber(input.value);
      input.value = val ? formatPercent(val) : "";
      calculateAll();
    });
    input.addEventListener("input", calculateAll);
  });

  downInputs.forEach((input, i) => {
    input.addEventListener("focus", () => { isEditingDown = true; input.value = parseNumber(input.value) || ""; });
    input.addEventListener("blur", () => { 
      isEditingDown = false;
      let val = parseNumber(input.value);
      input.value = val ? formatMoney(val) : "";
      calculateAll();
    });
    input.addEventListener("input", () => {
      const P = parseNumber(amountInput.value);
      if (!P) return;
      const val = parseNumber(input.value);
      downPercentInputs[i].value = ((val / P) * 100).toFixed(2);
      calculateAll();
    });
  });

  rateFields.forEach((input) => {
    input.addEventListener("focus", () => { input.value = parseNumber(input.value) || ""; });
    input.addEventListener("blur", () => { 
      let val = parseNumber(input.value);
      if (!val) val = 3.0;
      input.value = val.toFixed(2);
      calculateAll();
    });
    input.addEventListener("input", calculateAll);
  });

  const monthlyDownSelect = document.getElementById("downPercent");
  monthlyDownSelect?.addEventListener("change", () => { calculateAll(); });

  amortizationSelects.forEach(el => el.addEventListener("change", calculateAll));
  frequencySelects.forEach(el => el.addEventListener("change", calculateAll));

  // ===== INIT =====
  calculateAll();

  downPercentInputs.forEach(input => {
    let val = parseNumber(input.value);
    input.value = val ? formatPercent(val) : "";
  });
  
 

});
</script>
<script>
document.addEventListener("DOMContentLoaded", () => {

  // ========= HELPERS =========
  function parseNumber(val) {
    return parseFloat(String(val).replace(/[^0-9.-]+/g, "")) || 0;
  }

  function formatMoney(val) {
    return "$" + Number(val).toLocaleString("en-US", {
      maximumFractionDigits: 0
    });
  }

  // ========= ELEMENTS =========
  const amountInput = document.querySelector("[data-amount]");
  const citySelect = document.querySelector("[data-city]");
  const yesBtn = document.getElementById("yesBtn");
  const noBtn = document.getElementById("noBtn");

  const provincialRow = document.querySelector(".tax-row:nth-child(1) span:last-child");
  const municipalRow = document.querySelector(".tax-row:nth-child(2) span:last-child");
  const rebateRow = document.querySelector(".tax-row:nth-child(3) span:last-child");
  const totalRow = document.querySelector(".tax-row:nth-child(4) span:last-child");

  const downPercentSelect = document.getElementById("downPercent1");

  let firstTimeBuyer = yesBtn?.classList.contains("active");


const cashbackYes = document.getElementById("cashbackYes");
const cashbackNo = document.getElementById("cashbackNo");

let cashbackEnabled = true;

// Toggle logic
cashbackYes?.addEventListener("click", () => {
  cashbackEnabled = true;
  cashbackYes.classList.add("active");
  cashbackNo.classList.remove("active");
  calculateAll();
});

cashbackNo?.addEventListener("click", () => {
  cashbackEnabled = false;
  cashbackNo.classList.add("active");
  cashbackYes.classList.remove("active");
  calculateAll();
});



  // ========= BUTTONS =========
  yesBtn?.addEventListener("click", () => {
    firstTimeBuyer = true;
    yesBtn.classList.add("active");
    noBtn.classList.remove("active");
    calculateAll();
  });

  noBtn?.addEventListener("click", () => {
    firstTimeBuyer = false;
    noBtn.classList.add("active");
    yesBtn.classList.remove("active");
    calculateAll();
  });

  // ========= LTT =========
  function calculateOntarioLTT(price) {
    let tax = 0;
    if(price > 2000000) { tax += (price - 2000000) * 0.025; price = 2000000; }
    if(price > 400000) { tax += (price - 400000) * 0.02; price = 400000; }
    if(price > 250000) { tax += (price - 250000) * 0.015; price = 250000; }
    if(price > 55000) { tax += (price - 55000) * 0.01; price = 55000; }
    if(price > 0) { tax += price * 0.005; }
    return tax;
  }

  function calculateTorontoLTT(price) {
    return calculateOntarioLTT(price); // same logic
  }

  function calculateRebate(amount, type) {
  if (!firstTimeBuyer) return 0;

  if (type === "ontario") {
    return Math.min(amount, 4000);
  }

  if (type === "toronto") {
    return Math.min(amount, 4000);
  }

  return 0;
}

 function calculateLTT(price, city) {
  if(price <= 0) {
    provincialRow.innerText = "$0";
    municipalRow.innerText = "$0";
    rebateRow.innerText = "$0";
    totalRow.innerText = "$0";
    return 0;
  }

  const ontario = calculateOntarioLTT(price);
  const toronto = (city === "toronto") ? calculateTorontoLTT(price) : 0;

  // ✅ Apply rebates separately
  const ontarioRebate = calculateRebate(ontario, "ontario");
  const torontoRebate = calculateRebate(toronto, "toronto");

  const totalRebate = ontarioRebate + torontoRebate;
  const total = ontario + toronto;
  const net = total - totalRebate;

  provincialRow.innerText = formatMoney(ontario);
  municipalRow.innerText = formatMoney(toronto);
  rebateRow.innerText = formatMoney(totalRebate);
  totalRow.innerText = formatMoney(net);

  return net;
}

  // ========= CASH CLOSE =========
  function updateCashClose(price, ltt) {
      const downPercent = parseFloat(downPercentSelect?.value) || 0;
      const downPayment = (downPercent / 100) * price;
    
      const pstInsurance = 0;
    
      const fees = {
        lawyer: 1500,
        title: 900,
        inspection: 300,
        appraisal: 300,
      };
    
      // ✅ Cashback calculation
      const cashback = cashbackEnabled ? (price * 0.015) : 0;
    
      const totalCash =
        downPayment +
        ltt +
        pstInsurance +
        fees.lawyer +
        fees.title +
        fees.inspection +
        fees.appraisal -
        cashback;
    
      // UI updates
      document.querySelector(".cash-right .line-item:nth-child(1) span:last-child").textContent = formatMoney(downPayment);
      document.querySelector(".cash-right .line-item:nth-child(2) span:last-child").textContent = formatMoney(ltt);
    
      document.querySelector(".cash-right .line-item:nth-child(3) input").value = formatMoney(fees.lawyer);
      document.querySelector(".cash-right .line-item:nth-child(4) input").value = formatMoney(fees.title);
      document.querySelector(".cash-right .line-item:nth-child(5) input").value = formatMoney(fees.inspection);
      document.querySelector(".cash-right .line-item:nth-child(6) input").value = formatMoney(fees.appraisal);
    
      // ✅ Optional: show cashback line (if you want UI)
      const cashbackRow = document.getElementById("cashback-amount");
      if (cashbackRow) {
        cashbackRow.textContent =  formatMoney(cashback);
      }
    
      document.getElementById("cash-close-total").textContent = formatMoney(totalCash);
    }

  // ========= MASTER FUNCTION =========
  function calculateAll() {
    const price = parseNumber(amountInput.value);
    const city = citySelect.value.toLowerCase();

    const ltt = calculateLTT(price, city);
    updateCashClose(price, ltt);
  }

  // ========= EVENTS =========
  amountInput?.addEventListener("input", calculateAll);
  citySelect?.addEventListener("change", calculateAll);
  downPercentSelect?.addEventListener("change", calculateAll);

  // ========= INIT =========
  calculateAll();

});
</script>


<script>
document.addEventListener("DOMContentLoaded", () => {

  const totalEl = document.getElementById("monthly-expenses-total");
  const inputs = document.querySelectorAll(".monthly-expenses .cash-right input");
  const downSelect = document.getElementById("downPercent");

  function parseNumber(val) {
    return parseFloat(String(val).replace(/[^0-9.-]+/g, "")) || 0;
  }

  function formatMoney(val) {
    return "$" + Number(val).toLocaleString("en-US", {
      maximumFractionDigits: 0
    });
  }

  // ✅ Get mortgage from your main calculator
  function getActiveMortgagePayment() {
  const el = document.getElementById("mortgage-payment");
  return el ? parseNumber(el.dataset.payment || el.innerText) : 0;
}

  function calculateMonthlyExpenses() {
  let total = 0;

  total += getActiveMortgagePayment();

  inputs.forEach(input => {
    total += parseNumber(input.value);
  });

  totalEl.textContent = formatMoney(total);
}

// 👇 ADD THIS LINE
window.calculateMonthlyExpenses = calculateMonthlyExpenses;

  // ✅ Format inputs nicely on blur
  inputs.forEach(input => {

    input.addEventListener("focus", () => {
      input.value = parseNumber(input.value) || "";
    });

    input.addEventListener("blur", () => {
      let val = parseNumber(input.value);
      input.value = val ? formatMoney(val) : "$0";
      calculateMonthlyExpenses();
    });

    input.addEventListener("input", calculateMonthlyExpenses);
  });

  downSelect.addEventListener("change", calculateMonthlyExpenses);

  // 🔁 Sync with your main calculator
  document.addEventListener("input", (e) => {
    if (e.target.matches("[data-amount], [data-percentage], [data-rate]")) {
      calculateMonthlyExpenses();
    }
  });

  // ✅ Initial format + calc
  inputs.forEach(input => {
    let val = parseNumber(input.value);
    input.value = val ? formatMoney(val) : "$0";
  });

  calculateMonthlyExpenses();

});
</script>



<script>
    
    document.addEventListener("DOMContentLoaded", () => {

  const toggleButtons = document.querySelectorAll(".monthly-expenses .btn-toggle");
  const maintenanceInput = document.querySelector(
    ".monthly-expenses .cash-right .line-item:nth-child(3) input"
  );

  function parseNumber(val) {
    return parseFloat(String(val).replace(/[^0-9.-]+/g, "")) || 0;
  }

  function formatMoney(val) {
    return "$" + Number(val).toLocaleString("en-US", {
      maximumFractionDigits: 0
    });
  }

  toggleButtons.forEach((btn) => {
    btn.addEventListener("click", () => {

      // Remove active from all
      toggleButtons.forEach(b => b.classList.remove("active"));

      // Add active to clicked
      btn.classList.add("active");

      // Check type
      const type = btn.textContent.trim().toLowerCase();

      if (type === "freehold") {
        maintenanceInput.value = formatMoney(0);
      } else {
        maintenanceInput.value = formatMoney(150);
      }

      // Recalculate totals
      if (window.calculateMonthlyExpenses) {
        window.calculateMonthlyExpenses();
      }
    });
  });

});






</script>