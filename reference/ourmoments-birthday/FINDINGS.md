# ourmoments.live/birthday — reference audit

Captured 2026-09-02. Raw artifacts in this folder: `page.html` (full page source),
`page.css` (combined stylesheet), `wizard-full.html` / `paywall-nudge.html` /
`modal-css-pretty.css` (extracted slices).

## Status: feature-complete pass done (2026-09-03)

### Animation-parity tiers
- **Tier 1 — DONE** (commits d41356a, 9909878): ~48 keyframes ported with their consuming selectors, DOM confetti replaced by the Canvas 2D particle engine, and scenes converted from display-toggling to a true asymmetric cross-fade with a 450ms advance debounce.
- **Tier 2 — DONE**: the blossom-tree canvas. Transcribed from the deobfuscated source with ZERO guessed constants; runs as a new `tree` scene between `teaser` and `title`, matching the reference's own scene order (their `scene-tree` sits in exactly that slot).
- **Tier 3 — TODO**: self-drawing cake + envelope/letter upgrade.
- **Tier 4 — TODO**: GSAP cupid film.


Everything below marked "cataloged, not yet built" has now been implemented
and deployed live, verified end-to-end, **except** items 4 (cinematic
bow-and-arrow opening) and 7 (in-app-browser escape overlay) — both left out
deliberately as disproportionate effort for the value they add. Added on top
of the original list: sound effects (Web Audio API oscillator tones — no
audio assets exist in the plugin, so synthesized rather than sourced/licensed
files), covering the "Sound on for the full magic" hint and mute toggle that
were also missing.

## Implemented in blush-moments-plugin (this pass)

**Exit-intent nudge on the paywall** — `builders/birthday.php`. Their real trigger
is *not* a mouse-leave/velocity detector — it's simpler: the paywall's × button
and Escape key are intercepted on the first attempt and show a loss-aversion
modal instead of closing:

- Emoji 🥺, "Wait — it isn't saved yet"
- "The cake, the balloons, your letter — everything you just made lives only on
  this screen right now. One step secures it forever."
- Primary: "Finish the surprise 💛" (returns to paywall, countdown keeps running)
- Ghost: "I'll let it go" (actually closes)
- Second close attempt (or "I'll let it go") closes for real — no infinite loop

Deployed live and verified via `openPaywall()` in console: nudge appears on
first ×, "Finish the surprise" returns to the untouched paywall, second × closes.

## Cataloged, not yet built (larger scope — separate pass recommended)

Roughly in order of value vs. effort:

1. **Midnight countdown lock** — if a birthday date is set in step 1, the real
   recipient page shows "The surprise unlocks at midnight / Some things are
   worth the wait" with a live HH:MM:SS countdown before revealing anything.
   Our step 1 already has the copy hint ("unlocks midnight magic") but no
   actual lock — currently a promise the product doesn't keep. Note: the other
   session's new `class-cron.php`/`class-events.php` may already be building
   toward this — check before implementing to avoid duplicate work.

2. **Two-stage title reveal** — teaser first ("Happy Birthday / to someone
   worth celebrating"), then a second personalized card ("IT'S OFFICIALLY YOUR
   DAY / Happy Birthday, {name}!"). We only show one generic title step.

3. **Swipeable photo carousel** ("A walk down memory lane / swipe through")
   instead of a static fairy-lights grid. More mobile-native.

4. **Cinematic opening film** — draggable bow-and-arrow that shoots a heart
   (GSAP physics), heart-morph transition, iris wipe into the dark scene. High
   production value, high effort — obfuscated JS, would need to be built from
   scratch rather than extracted. Lowest priority given cost/benefit.

5. **Unwrap gate** — landing card ("A SURPRISE FOR {name} / {from} made this —
   just for you. / Unwrap it / Sound on for the full magic") before the
   experience starts, likely to satisfy audio-autoplay-needs-interaction rules.

6. **Testimonials strip inside the paywall** ("💬 Our happy customers",
   auto-scrolling, pauses on hover/touch) — sits above the price box.

7. **In-app-browser escape overlay** — detects when the link is opened inside
   Instagram/WhatsApp's embedded browser (where Razorpay checkout can break)
   and prompts to open in a real browser.

8. **WhatsApp share modal post-payment** — dedicated "Send on WhatsApp 💬" /
   "Copy the link 📋" / "👁️ See what they'll see" modal, richer than our
   current bare copy-link step.

9. **Expired-link state** — after 90 days, a dedicated "🕯️ This surprise has
   floated away" screen instead of a broken/blank page.

Their paywall pricing: ₹499→₹199 (60% off), green price treatment, not our
purple/gold. Left ours as-is (₹399→₹149) since pricing is a business decision,
not a design-parity one.
