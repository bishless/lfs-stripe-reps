# LFS Stripe Reports

View and download custom reports for payouts your organization has collected via Stripe.

## Roadmap

- [x] Phase 1: Display reports
- [x] Phase 2: Provide CSV downloads ... = 1.0 release
- [ ] Phase 3: Provide Email funx ... = 1.5 release
- [ ] Phase 4: Provide Scheduled reports/emails ... = 2.0 release




Currently making use of custom role: 'stripe_reports' via [Members](https://wordpress.org/plugins/members/)




IDEA: **Dashboard Widget?** Just show latest payout. Download straight from the front page? (behind role check, of course)

- [x] add submenu page for Options/settings
- [x] get API key and API ver saving as settings
- [x] actually read API key and API ver from db to render table rows
- [ ] better styling for status 'pills'
- [x] add 'no API key set' state in payout table
- [ ] add 'no payouts' state in render_payout_rows
- [ ] add error/no-connection state in render_payout_rows
- [ ] add instructions for Members dependency and role creation
- [x] add plugin header info for use with Github Updater
