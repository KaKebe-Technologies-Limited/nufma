# Content handover — placeholders and approvals

The website never invents NUFA facts. Anything below is either **awaiting confirmation** (hidden or clearly labelled on the site) or **carried over from the earlier live site and needs verification** before it is treated as final.

## A. Statements awaiting NUFA leadership approval
| Item | Where | Status on the site |
|---|---|---|
| Vision and mission statements | About NUFA → Page Content (About) | Shown with a **"Draft — pending leadership approval"** label. Tick *Site Settings → Vision & mission approved* to remove the label. |
| Membership terms, criteria and fee | Membership → Membership settings; Policies → Membership Terms | Criteria shown as *"awaiting confirmation"*; fee/payment text is a placeholder. |
| Complaints and feedback process | Policies → Complaints | Short interim text only. |

## B. Legal documents (need review by a qualified professional)
Privacy Policy, Cookie Policy, Terms of Use, Image & Content Permissions, Accessibility Statement. Drafts are written from the site's real data collection. They display a **"Draft for review"** notice until *Site Settings → Policy pages reviewed* is ticked.

## C. Information needed from NUFA
1. **Leadership** — verified names, official positions, short biographies and approved portraits (admin → Leadership). Current entries and their bright-orange portrait backgrounds were carried over from the earlier site; biographies are empty.
2. **Programmes** — for each of the five programme areas: context/need, objectives, beneficiaries, outputs, geography, partners, progress reports, and a **status** (Planned / Ongoing / Completed / Under Development) *only when confirmed*. Until then no status label is shown.
3. **NUFA SACCO** — remains *proposed / under development*. Provide formal progress updates when available; do not change the status wording without evidence of registration and authorisation.
4. **Awards**
   * NUFA Awards **2025**: official categories, nominees, winners, sponsors, press coverage, video highlights. Date set to **26 April 2025** at Acholi Inn per the brief (the earlier site said 25 April — please confirm).
   * NUFA Awards **2026 (and its winners/statistics)** and **2027**: these were carried over from the earlier site. Please confirm every figure (e.g. attendee and submission counts, number of categories) or clear the statistics in *Award Editions*. The 2027 page now shows no dates, tickets or categories until confirmed.
   * **Tickets**: hidden until *Site Settings → Ticket information confirmed* is ticked.
   * Official **submission platform link**, if one is used (Page Content → NUFA Awards).
5. **Partners** — confirm which listed organisations are *current partners* and which are *previous supporters* (admin → Partners → Relationship). Provide approved logos.
6. **News articles** — the ten articles imported from the earlier site are the association's own posts; please confirm each is accurate and approved. Mark any demonstration content as unpublished.
7. **Impact figures** — none are shown. Add verified indicators in *Site Settings → Verified impact figures* when available.
8. **Resources and videos** — the library is empty by design. Upload approved documents (Resources) and official YouTube/Vimeo links (Gallery & Videos).
9. **Contact details** — set from the brief: Plot 75, Jomo Kenyatta Road, Gulu City · nuf.association@gmail.com · +256 774 757 587 / +256 779 712 990 · https://www.nufa.media/. A map is intentionally **not** shown until the exact office location is verified.
10. **Photographs** — the images used come from NUFA's own galleries and productions. Please confirm publication permission for people shown, and add photographer credits (News → image credit; Gallery note).
11. **Membership payment details** — real mobile-money/bank details, fee, validity period, president's name and signature (Membership → Membership settings).
12. **Sending e-mail address** — an address on the hosting domain (Membership settings → Emails) for reliable delivery.

## C1. Programme support (donate · sponsor · volunteer · partner)
* Each programme page now has a **Get involved** section and a request form (`support.php`). Requests are reviewed in **admin → Support requests**.
* **Payment methods are empty by design.** Add the real mobile-money/bank details NUFA controls in **Payment & giving setup** — until then supporters are told payment details will be e-mailed.
* Sponsorship levels, suggested amounts and minimum gift are **not preset** (no invented packages or figures). Add them in Giving settings if NUFA has confirmed them.
* The five programmes were marked **Ongoing** and visible on the instruction that they are active; change a status in Programmes if that is not accurate.
* Have the declaration text and any refund/gift terms reviewed by a qualified professional.

## C2. Removed or replaced
* Testimonial stock photographs and a stock ticket illustration were removed (unrelated to NUFA).
* Fabricated-looking testimonials, hard-coded statistics (people reached, submissions, etc.), the "Registration Open"/"nominations open soon" claims for 2027, and the newsletter sign-up (no delivery service is configured) were removed.
* A news article thumbnail showing an unrelated object was replaced with an awards photograph.
* Unused legacy images were moved to `docs/image-archive/` (not publicly served).

## D. Go-live checklist
See `docs/DEPLOYMENT.md`.

## NUFA SACCO (Admin → NUFA SACCO)
- **Status & regulatory**: Proposed / In Development / Operational, application switch, approved vision/mission flag, regulator, registration number, licence status and date. Keep it Proposed until registration and licensing are verified; applications only show when the switch is on.
- **Products**: mark each as Proposed or Approved. Eligibility, terms, interest and fees are public only for Approved products.
- **Documents**: upload PDF/Word/Excel/ZIP (stored in `assets/sacco-docs/`) or link out. Nothing is listed unless added here.
- **Announcements**: dated official notices.
- **Visits & enquiries**: anonymous daily visit counts (no cookies or IPs) and SACCO submissions.
- Page wording and section visibility: Page Editor → SACCO.

## NUFA Awards page (Admin → Awards setup)
- **Submission portal** (status, link, dates, fee, eligibility, guidelines) and **Tickets & booking** (status, ticket types, purchase link, how to book; booking requests arrive under Enquiries → Ticket requests).
- **Road map, Guests, Key moments, Impact stories, Highlight films**: add items per edition; the road map is pre-filled with placeholder steps (no dates) for you to complete.
- **Winners**: Award Winners now takes an optional note and picture per winner. The page shows the last two past editions.
- **Technical team**: add people in Team with the category “Awards Technical Team” (they appear on the Awards page, not on About).
- Teaser video, take-part ways, FAQ, eligibility/judging text and every placeholder message are editable in Page Editor → NUFA Awards.

## News, stories & newsletters (Admin → News & Stories / Newsletters / Subscribers)
- **Stories**: write with the editor (headings, lists, links, quotes, inline picture upload), add a featured image, optional video link and PDF attachment; save as Draft or Published.
- **Newsletters**: either *write one here* (editor, cover, “In this issue” story picks, optional PDF version) or *upload a PDF*. Published issues appear on the Newsletter page (archive + online reader + PDF download).
- **E-mailing**: open a published newsletter → send a test, then “Send to subscribers” (40 per click, nobody gets the same issue twice; failed addresses can be retried).
- **Subscribers**: visitors join on the Newsletter page; add addresses (only people who agreed), pause, delete or export CSV. Every email carries an unsubscribe link.

## Roles & permissions (Admin → Admin Users / Roles & permissions)
- Roles: Administrator, President, NUFA Event Manager, HR, Finance Manager, Technical Lead, Sacco Manager, NUFA Secretary, Head of Protocol (plus any custom role). Each role gets *No access / View only / Manage* per area (membership, team, enquiries, support & payments, SACCO, schedule, awards, invitations, news, website, settings, users).
- Assign a role when creating an admin or change it in the Admin Users table. The sidebar hides what a role cannot open; view-only roles cannot save, delete or send anything. Existing admins stay Administrators.

## Award invitations (Admin → Award invitations)
1. Create an invitation, upload your PDF invitation, write the email (placeholders {full_name}, {event}, {date}, {venue}).
2. Set where the name goes (mm from the top-left) and open the preview until it sits right.
3. Add guests: paste “Title | Name | Email | Organisation” lines (or upload a CSV, or add all approved members).
4. Send a test, then send in batches of 25. Guests get a personalised PDF and Yes/No buttons; replies show on the Send & responses tab and in the CSV export.
- The PDF is personalised by adding the text to the template (the original is untouched). Encrypted PDFs can't be edited; they are attached unchanged.

## SACCO membership application (Admin → SACCO applications)
- The public form is `sacco-apply.php`. It accepts applications **only** while “Accept membership applications” is ticked in SACCO manager → Status & regulatory (leave it off until the SACCO's registration is verified); otherwise visitors are pointed to the interest form.
- The form collects personal details, residence, work and NUFA membership, savings plan (shares, deposit, regular savings, products of interest), next of kin, beneficiary, introducer, registration fee payment, ID/photo/proof documents and a three-part declaration with typed signature.
- Every applicant receives a registration number automatically at submission (default `NUFA-SACCO/2026/0001`); prefix, format, digits and the next number are set under Form, fees & numbering.
- Review applications, open the uploaded documents (private), tick “fee received”, approve or reject (the applicant is emailed), and export CSV.

## Partnership applications (Admin → Partnership applications)
- Public form: `partner-apply.php` (linked from Partner With Us → “Co-host or partner on an activity”). Individuals and organisations give their details, pick the activity/outreach, say how they will partner and what they will contribute (value, timing, recognition) and may upload a logo.
- Review → **Approve** (state the agreed commitment, due date, website category) → when the commitment is delivered press **Commitment met**: the partner's logo and details are added to the Partners page automatically (and shown under “Recent co-hosts & contributors”), and they are thanked by email. Turn auto-publish off per partner or globally if you prefer to publish by hand. **Remove from website** or reject at any time.

## Feedback (Admin → Feedback)
- Public page `feedback.php` (Contact menu → Feedback; also “Give feedback” on each programme page): what it is about (programme, activity/event, initiative, website, general) and which one, star rating, type (praise / suggestion / concern / question), the feedback itself, optional improvements, took part?, would recommend?, and identity — **anonymous** (no name, email or phone is kept) or named.
- Admin: inbox with filters and search, status (new/reviewed/actioned/archived), internal notes, reply by email (named senders with an email), tick “Show on the Feedback page” to publish a comment (anonymous unless the sender agreed to share their name), **Insights** (average rating, distribution, by area, most-discussed programmes) and CSV export. Wording and on/off in Settings and Page Editor → Feedback.

## WhatsApp, mobile and SACCO status
- **WhatsApp** (Admin → Appearance & Branding): a floating “Chat with us” button, a WhatsApp link in the contact bar, the Contact page and the Feedback page open a chat with the number you set (blank = the main telephone) with a pre-typed message. In the admin, “Reply on WhatsApp” appears on enquiries, feedback, SACCO and partnership applications whenever the person left a phone number.
- **SACCO status** now has an **Ongoing** option (Admin → NUFA SACCO → Status & regulatory); the SACCO page, the Programmes card and the footer show “Ongoing” instead of “Proposed”. Registration and licence details still come only from the regulatory fields you fill in.
- Mobile: layouts, forms, tap targets and the top bar were tuned for phones (no sideways scrolling on any public page).

## Admin redesign, dashboard, exports & holiday greetings
- **New admin look**: charcoal sidebar grouped by task (People · Inbox · Events & awards · Stories & media · Programmes & partners · Website · System), a top bar with **search / jump-to (Ctrl+K or /)**, a **New** menu and a **bell** listing what needs attention. Tables are cleaner, the News & stories list has status tabs, search, filters and sorting.
- **Dashboard**: what needs attention, KPIs, quick actions, 30-day website visits (anonymous), membership trend, guild split, upcoming events & holidays, recent activity, most visited pages, reports & downloads and a health check.
- **Exports**: every admin table has an **Export** button — PDF, Excel (.xlsx), Word (.docx) or CSV — for exactly the rows on screen; the main lists (members, enquiries, subscribers, support, SACCO, partnerships, feedback, stories, invitation guests) export the full filtered data.
- **Holiday greetings** (Calendar → Holiday greetings): every morning the calendar is checked and registered members (and/or newsletter subscribers) receive your message on public holidays or special days. Write a default message and a different one for any day, send a test, or send now. Needs the daily task from Calendar → Email & SMS settings; if none is set up, it also runs the first time an admin opens the dashboard each day.

## SACCO membership: fee, shares and card
- Every applicant pays the **membership (registration) fee and at least two shares** with the application: the form requires the payment method, reference, date, total amount (checked against fee + shares × price when both are set as numbers) and proof of payment, and shows a live total to pay. Set the fee and share price in SACCO applications → Form, fees & numbering (the minimum is always 2 shares).
- On **Approve, issue card & email** the admin first ticks that the payment proof was verified and confirms the shares paid (≥ 2). The system then issues a **membership card** (`sacco-card.php?t=…`: photo, name, member number, shares held, member since, validity, signature) and emails the link; the page also verifies the member. Resend the card link any time; set signer, note and validity under “Membership card”.
