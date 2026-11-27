<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>DentaCare</title>
<style>
:root{--navy:#0f2b5b;--blue:#1d4ed8;--bg:#f4f6fb;--card:#fff;--ink:#0b1630;--muted:#5b6b8a;--line:#e3e8f2;--tint:#e0e7fb;}
*{box-sizing:border-box;margin:0}
body{font-family:Inter,"Segoe UI",system-ui,sans-serif;color:var(--ink);background:var(--bg);line-height:1.5}
a{color:inherit;text-decoration:none}
.wrap{max-width:1040px;margin:0 auto;padding:0 24px}
.btn{display:inline-block;padding:14px 26px;border-radius:10px;font-weight:600;border:1.5px solid var(--blue);cursor:pointer;font:inherit;font-weight:600}
.btn.primary{background:var(--blue);color:#fff}
.btn.ghost{background:transparent;color:#fff;border-color:#fff}
:focus-visible{outline:3px solid #93b4ff;outline-offset:2px}

/* dark top: nav + hero */
.top{background:var(--navy);color:#fff;padding-bottom:72px}
nav{display:flex;align-items:center;justify-content:space-between;height:80px}
.logo{font-weight:700;letter-spacing:.12em;font-size:20px}
.links{display:flex;gap:28px;align-items:center}
.links a{opacity:.9}.links a:hover{opacity:1;text-decoration:underline}
.hero{text-align:center;padding-top:40px}
.hero h1{font-size:clamp(32px,5vw,52px);line-height:1.12;max-width:760px;margin:0 auto 16px}
.hero .lead{font-size:19px;max-width:640px;margin:0 auto 8px;color:#cfd9f2}
.hero .small{font-size:14px;color:#9fb0d6;margin-bottom:36px}
.visual{max-width:760px;margin:0 auto 36px;aspect-ratio:16/8;border-radius:20px;background:linear-gradient(135deg,#1d4ed8,#12306b);display:grid;place-items:center;position:relative;overflow:hidden}
.visual svg{width:34%;height:auto}
.chip{position:absolute;background:#fff;color:var(--ink);border-radius:12px;padding:10px 14px;font-size:14px;text-align:left;box-shadow:0 6px 20px rgba(0,0,0,.18)}
.chip b{display:block;font-size:13px;color:var(--muted);font-weight:500}
.chip.a{left:6%;top:16%}.chip.b{right:6%;bottom:16%}
.cta{display:flex;gap:16px;justify-content:center;flex-wrap:wrap}

/* light middle */
.mid{padding:72px 0}
h2{font-size:clamp(26px,3.4vw,34px);text-align:center;margin-bottom:8px}
.sub{text-align:center;color:var(--muted);margin-bottom:28px}
.search{display:block;max-width:460px;margin:0 auto 40px;width:100%;padding:14px 18px;border:1px solid var(--line);border-radius:12px;font:inherit;background:#fff}
.cards{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;align-items:stretch;margin-bottom:72px}
.card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:24px}
.card.feat{border:2px solid var(--blue);box-shadow:0 10px 30px rgba(29,78,216,.12);transform:translateY(-8px)}
.ico{width:44px;height:44px;border-radius:12px;background:var(--tint);color:var(--blue);display:grid;place-items:center;margin-bottom:14px}
.ico svg{width:22px;height:22px}
.card h3{font-size:18px;margin-bottom:6px}
.card p{color:var(--muted);font-size:15px}
.card a{display:inline-block;margin-top:14px;color:var(--blue);font-weight:600}
.svc{display:flex;gap:28px;justify-content:center;flex-wrap:wrap;margin-top:32px}
.svc div{width:120px;text-align:center}
.circle{width:96px;height:96px;border-radius:50%;background:var(--tint);color:var(--blue);display:grid;place-items:center;margin:0 auto 10px;border:1px solid var(--line)}
.circle svg{width:38px;height:38px}
.svc span{font-weight:600;font-size:15px}
.empty{text-align:center;color:var(--muted);display:none;margin-top:24px}

/* dark bottom */
.faq{background:var(--navy);color:#fff;padding:64px 0 24px}
.faq h2{color:#fff}
.faq .sub{color:#9fb0d6}
.faq details{background:rgba(255,255,255,.07);border-radius:12px;margin:0 auto 10px;max-width:760px}
.faq summary{padding:16px 20px;cursor:pointer;font-weight:600;list-style:none;display:flex;justify-content:space-between;gap:12px}
.faq summary::after{content:"+";font-size:20px;line-height:1}
.faq details[open] summary::after{content:"\2212"}
.faq details p{padding:0 20px 16px;color:#cfd9f2}
footer{max-width:760px;margin:40px auto 0;padding:20px 24px 0;border-top:1px solid rgba(255,255,255,.15);display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px;color:#9fb0d6;font-size:14px}
footer a:hover{color:#fff;text-decoration:underline}

@media(max-width:760px){.links a:not(.btn){display:none}.cards{grid-template-columns:1fr}.card.feat{transform:none}.chip{display:none}.visual{aspect-ratio:16/10}}
</style>
</head>
<body>

<header class="top">
  <div class="wrap">
    <nav aria-label="Main">
      <a class="logo" href="{{ route('welcome') }}">DENTACARE</a>
      <div class="links">
        <a href="#features">Features</a>
        <a href="#services">Services</a>
        <a href="#faq">FAQ</a>
        <a class="btn primary" style="padding:10px 20px" href="{{ route('login') }}">Sign in</a>
        <a class="btn ghost" style="padding:10px 20px" href="{{ route('signup') }}">Sign up</a>
      </div>
    </nav>
    <div class="hero">
      <h1>Dental care that fits your schedule</h1>
      <p class="lead">Book visits, check your symptoms and keep track of your care plan, all in one place.</p>
      <p class="small">Trusted by patients and front desk teams across the clinic.</p>
      <div class="visual" role="img" aria-label="Illustration of a tooth with appointment cards">
        <div class="chip a"><b>Next visit</b>Routine check-up</div>
        <svg viewBox="0 0 64 64" fill="none"><path d="M32 8c-5-4-16-3-19 6-2 7 1 13 3 20 2 8 3 22 8 22 4 0 4-12 8-12s4 12 8 12c5 0 6-14 8-22 2-7 5-13 3-20-3-9-14-10-19-6z" fill="#fff"/><path d="M22 20c2-2 5-2 7 0" stroke="#1d4ed8" stroke-width="2.5" stroke-linecap="round"/></svg>
        <div class="chip b"><b>Care plan</b>75% complete</div>
      </div>
      <div class="cta">
        <a class="btn primary" href="{{ route('login') }}">Book appointment</a>
        <a class="btn ghost" href="{{ route('login') }}">Try symptom checker</a>
      </div>
    </div>
  </div>
</header>

<main class="mid">
  <div class="wrap">
    <section id="features">
      <h2>Everything you need for your dental care</h2>
      <p class="sub">Search for a service or pick a feature to get started.</p>
      <input class="search" id="q" type="search" placeholder="Search services, e.g. cleaning" aria-label="Search services">

      <div class="cards">
        <article class="card">
          <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg></div>
          <h3>Book online</h3>
          <p>Pick a dentist and a time that works for you. Reschedule anytime.</p>
          <a href="{{ route('login') }}">Book now</a>
        </article>
        <article class="card feat">
          <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 21s-7-4.5-7-11a4 4 0 0 1 7-2.5A4 4 0 0 1 19 10c0 6.500-7 11-7 11z"/></svg></div>
          <h3>Symptom checker</h3>
          <p>Describe how you feel and get a predicted result. It is not a diagnosis.</p>
          <a href="{{ route('login') }}">Check symptoms</a>
        </article>
        <article class="card">
          <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg></div>
          <h3>Care plan</h3>
          <p>See your visit history and what is next, like your six-month cleaning.</p>
          <a href="{{ route('login') }}">View my care</a>
        </article>
      </div>
    </section>

    <section id="services">
      <h2>Our services</h2>
      <p class="sub">Common treatments we offer.</p>
      <div class="svc" id="svc">
        <div data-n="cleaning"><div class="circle"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3l1.800 4.200L18 9l-4.200 1.800L12 15l-1.800-4.200L6 9l4.200-1.800zM19 15l.8 1.800L21.500 17.500l-1.700.7L19 20l-.8-1.800-1.700-.7 1.700-.7z"/></svg></div><span>Cleaning</span></div>
        <div data-n="check-up checkup"><div class="circle"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="6"/><path d="M20 20l-4.500-4.500"/></svg></div><span>Check-up</span></div>
        <div data-n="filling"><div class="circle"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5c-2-1.500-6-1-7 2.500-.8 3 .5 5 1.200 8 .8 3 1.300 5 3 5s1.500-4 2.800-4 1.100 4 2.800 4 2.200-2 3-5c.7-3 2-5 1.200-8-1-3.500-5-4-7-2.500z"/></svg></div><span>Filling</span></div>
        <div data-n="x-ray xray"><div class="circle"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="4" y="4" width="16" height="16" rx="3"/><path d="M9 8v8M15 8v8M9 12h6"/></svg></div><span>X-ray</span></div>
        <div data-n="consultation"><div class="circle"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 5h16v11H9l-5 4z"/></svg></div><span>Consultation</span></div>
      </div>
      <p class="empty" id="empty">No services match your search. Try another word.</p>
    </section>
  </div>
</main>

<section class="faq" id="faq">
  <div class="wrap">
    <h2>Questions patients ask</h2>
    <p class="sub">Quick answers before you book.</p>
    <details open><summary>How do I book an appointment?</summary><p>Sign in, choose a service, dentist, date and time, then confirm.</p></details>
    <details><summary>Can I reschedule or cancel a visit?</summary><p>Yes. Open Appointments and choose Reschedule or Cancel on an upcoming visit.</p></details>
    <details><summary>Is the symptom checker a diagnosis?</summary><p>No. It gives a predicted result only. Your dentist makes the diagnosis.</p></details>
    <details><summary>Do you accept walk-ins?</summary><p>Yes. Front desk adds walk-ins to the queue and sees them in order.</p></details>
    <details><summary>Will I get reminders?</summary><p>We send appointment and recall notices by SMS or email.</p></details>
    <details><summary>Who can see my records?</summary><p>Only authorized clinic staff and your assigned dentist.</p></details>
    <details><summary>How do I update my data?</summary><p>Contact the clinic to access, correct or request deletion of your data.</p></details>
    <details id="privacy"><summary>Privacy notice</summary><p>We collect patient contact details, dental history and appointment records to coordinate care. Only authorized clinic staff and the assigned dentist should access these records. Contact the clinic to access, correct or request deletion of your information. Symptom checker results are predictions, not a diagnosis.</p></details>
    <footer>
      <span>&copy; DentaCare</span>
      <a href="#privacy">Privacy Notice</a>
    </footer>
  </div>
</section>

<script>
const q=document.getElementById('q'),items=[...document.querySelectorAll('#svc > div')],empty=document.getElementById('empty');
q.addEventListener('input',()=>{
  const t=q.value.trim().toLowerCase();let n=0;
  items.forEach(i=>{const m=!t||(i.dataset.n+' '+i.textContent).toLowerCase().includes(t);i.style.display=m?'':'none';if(m)n++;});
  empty.style.display=n?'none':'block';
});
</script>
</body>
</html>
