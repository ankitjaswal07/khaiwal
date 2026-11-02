#!/usr/bin/env python3
"""Builds data/game-content.json (slug -> block-editor markup) and checks the 300-400 word target.
Run from the theme root:  python3 tools/build-game-content.py
Text is informational only. [khaiwal_game] shortcodes are replaced by live values on the front end."""
import json, re, sys, os

T = '[khaiwal_game field="time"]'
LATEST = '[khaiwal_game field="latest"]'
LDATE = '[khaiwal_game field="latest_date"]'

# slug: (intro, timing, chart)  -- each paragraph is written for that game only.
G = {}

G['model-town'] = (
"Model Town is the first game on the daily board, which makes this page the place where each new day begins. When the calendar turns over, the Model Town row for the day is the first one waiting to be filled, and many visitors open it before anything else simply to confirm that the board is up to date. Everything here is informational: the page records published two-digit results and the date each one belongs to, and it never forecasts, ranks or recommends numbers.",
f"Model Town is listed at {T}, the earliest slot in the lineup. The next game, Sadar Bazar, follows 45 minutes later at 1:25 PM IST, so there is a short window in which Model Town is the only entry that can have been announced. Until the editors publish the number, today’s cell shows the bouncing WAIT word instead of a value. That word is a status, not a result, and it is replaced by the number as soon as the entry is saved. All times on this site are Indian Standard Time, so visitors in other countries should convert before comparing.",
f"The two tables below the summary show the selected month and the month before it, one row per calendar date. Pick another month from the selector to look further back. The most recent published number for this game is {LATEST}, recorded for {LDATE}. A dash means no entry has been published for that date, which is different from a result of zero. If you are comparing Model Town with other games, the full chart page lays every game side by side for the same month, and the JSON link beside the quick answer returns this month’s data for software and AI assistants."
)

G['sadar-bazar'] = (
"Sadar Bazar sits in the early afternoon of the daily lineup and is usually the second page people open after checking the first game of the day. This page keeps a dated record of every published Sadar Bazar number so that you can find a particular day without scrolling through a long list. It is an archive and a status board, nothing more: no prediction, tip or suggestion is offered, and nothing on the page should be read as advice.",
f"Sadar Bazar is listed at {T}. It follows Model Town by 45 minutes and is followed by Gwalior one hour later, so it is the first game in the lineup that comes with a comfortable gap on both sides. During that gap the today cell shows WAIT with a gentle bounce, and the notice bar at the top of every page carries the same status in its scrolling line. As soon as an editor publishes the number, both places switch from the waiting word to the value without any need to reload. Remember that every clock on the site runs on Indian Standard Time.",
f"Use the month selector to move between months, and compare the two tables to see how the current month is filling up against the last one. The latest number recorded for Sadar Bazar is {LATEST} on {LDATE}. Bold numerals make the values easy to scan on a phone, while a dash marks a date with nothing published. When you want to cite the data, the quick answer at the top of the page gives a plain sentence that can be copied, and the JSON link provides the same figures in a machine-readable form for developers and assistants."
)

G['gwalior'] = (
"Gwalior is the third game on the board and the first of a tight afternoon cluster. Visitors often keep this page open in a tab because the games that surround it follow each other closely, and a quick refresh tells them whether the number has been published. The page lists Gwalior’s published results by date and offers nothing beyond that: it does not predict outcomes, and it is not a place to place or arrange any kind of wager.",
f"The scheduled time for Gwalior is {T}. Sadar Bazar comes one hour before it, and Delhi Bazar follows only 25 minutes after, which is the shortest gap anywhere in the lineup. Because of that, the status line for Gwalior can change from waiting to published and the very next game can start waiting within the same half hour. The bouncing WAIT word on the today cell and in the scrolling notice bar always reflects the state of this game alone, so you can tell at a glance whether Gwalior itself has been announced.",
f"Scroll down to the monthly tables to read Gwalior date by date. The selected month is shown first and the month before it second, which is handy when a month has just begun and the current table is still short. Gwalior’s most recent published number is {LATEST}, dated {LDATE}. Blank dates appear as dashes and are never filled with guesses. If you maintain your own records, the chart page offers every game for the same month, and the public JSON endpoint returns the same entries that are shown here, so the numbers always match."
)

G['delhi-bazar'] = (
"Delhi Bazar is the fourth game in the daily order, and its page is built for people who like to see a single game clearly rather than the whole board at once. You will find the current status, a short quick-answer summary and two monthly tables. The content is informational and archival. Nothing here tells you what a future number might be, and the site does not take bets, payments or entries of any sort.",
f"Delhi Bazar is listed at {T}. It arrives just 25 minutes after Gwalior and about 50 minutes before Punjab Matka, so the middle of the afternoon is the busiest stretch of the schedule. If you open the page between games, you may see yesterday’s value in the previous-day box and the WAIT word in today’s box, which is exactly what a game that has not been announced yet should look like. After the editors publish, the number replaces WAIT and appears in bold. The times are in Indian Standard Time, with no daylight-saving changes to worry about.",
f"The summary at the top compares today with the previous day, and shows the latest published figure, currently {LATEST} for {LDATE}. Underneath, the monthly tables list each date in order. The row for today is highlighted so that it stays easy to find even in a long month. A dash indicates a date without a published entry. To compare Delhi Bazar with neighbouring games, open the full chart, where every game shares one calendar, or use the month selector here to review earlier periods of this game on its own."
)

G['punjab-matka'] = (
"Punjab Matka is the fifth game in the schedule and is shown here under the name used in the site’s catalogue. The name is simply a label for the listing, and the page treats it like any other entry: it records the dated numbers that editors publish and explains how to read them. It does not provide forecasts, and it does not connect visitors to any betting or payment service, because that is outside what this site does.",
f"Punjab Matka is scheduled for {T}. Delhi Bazar comes 50 minutes earlier and Shri Ganesh follows 45 minutes later, which places this game in the middle of the late-afternoon run. Before the number is out, the today cell and the scrolling notice bar both show the bouncing WAIT word. Once it is published, the figure appears in bold and the notice bar updates by itself. The site checks the Indian Standard Time clock in your browser, so the waiting state turns on and off at the right moments even if you leave the tab open for hours.",
f"On this page the monthly tables are the heart of the record. The newest table is for the month you selected, and the second one is the month before. The most recent published value is {LATEST}, which belongs to {LDATE}. Dates with no entry show a dash, which only means that nothing has been recorded. If you want to double-check a number, open the JSON link beside the quick answer: it returns the same entries in a structured format, along with the date each one is attached to."
)

G['shri-ganesh'] = (
"Shri Ganesh is the sixth game on the board, falling in the late afternoon, and its page works as a simple dated ledger. Each row pairs a calendar date with the two-digit number published for it. That is the whole purpose of the page. It is not a source of tips, it does not predict the next value, and it is not connected to any form of play or payment, so please treat it purely as reference information.",
f"The listed time is {T}. Punjab Matka precedes it by 45 minutes, while Agra follows 55 minutes later, giving Shri Ganesh a fairly even rhythm on both sides. This is also roughly the point at which the working day winds down for many visitors, so the page often gets checked just after the scheduled time. If the number has not been published yet, you will see WAIT with a lively bounce in the today cell, in the hero card and in the notice bar. The moment the entry is saved, the number takes its place and the animation stops.",
f"To read the record, start with the quick answer, which states today’s status, the previous day’s value and the latest published number, {LATEST} on {LDATE}. Then scan the two monthly tables. Bold numerals mark published results, and dashes mark dates with nothing recorded. A month that has only just begun will look sparse, so the earlier table is useful for context. Developers can pull the same month in JSON, and anyone comparing several games can switch to the full chart page and read all columns together."
)

G['agra'] = (
"Agra opens the evening stretch of the daily lineup, and this page is arranged so that the information you need most is at the top. A short quick-answer box gives the status for today, the previous day’s value and the latest published number, and the tables below provide the longer record. All content is informational. There are no predictions, no recommendations and no way to take part in any game from this site.",
f"Agra is listed at {T}, which is 55 minutes after Shri Ganesh and only 35 minutes before Faridabad. That short second gap means the Agra status is worth checking promptly after its scheduled time, because the next game begins to wait soon afterwards. While Agra has not been announced, its today cell shows the bouncing WAIT word. After publication the number appears in bold and the waiting animation disappears. Times are given in Indian Standard Time, so adjust them if you are reading from another time zone, and note that the notice bar at the top repeats the same information.",
f"For a longer view, use the month selector to go back, and compare the current table with the previous month. The latest published Agra number is {LATEST}, recorded for {LDATE}. Missing dates are shown as dashes rather than being guessed. The site also publishes its data for software: the JSON link in the quick answer returns this month’s Agra entries, and the chart page lists every game for the same period, which is the quickest way to cross-check this record against others."
)

G['faridabad'] = (
"Faridabad is the eighth game in the lineup and closes out the early evening block. People tend to open this page at the end of the working day to see whether the number has been published and to look up an earlier date. The page is a record, not a guide: it contains no forecasts, no hints about future numbers and no route to betting, and it should be used only as dated reference material.",
f"Faridabad is scheduled for {T}. Agra comes 35 minutes before it, and then there is the longest daytime pause in the schedule, since Alwar does not follow until 7:25 PM IST, a gap of 90 minutes. Because of that pause, Faridabad’s published number stays the freshest result on the board for some time. Until the number is out, the today cell shows the animated WAIT word, and the same state is mirrored in the scrolling bar at the top of the page. All timings use Indian Standard Time.",
f"The quick answer at the top lists the current status, yesterday’s value and the newest published number, {LATEST} on {LDATE}. After that come two tables, one for the selected month and one for the month before. Entries are bold and easy to read on small screens, and a dash means nothing has been published for the date. Switch months with the selector to go further back. If you need the data elsewhere, the JSON link returns the same entries, and the chart page lets you view Faridabad next to every other game."
)

G['alwar'] = (
"Alwar starts the night block of the daily lineup, following the longest break of the evening. Its page is intentionally plain: a status summary, two monthly tables and a few lines explaining how to read them. Everything is informational. The site does not predict numbers, does not suggest what to expect and does not offer any way to play, pay or place an entry in any game.",
f"The usual time for Alwar is {T}. Faridabad finishes the earlier block at 5:55 PM IST, so Alwar returns after a 90-minute pause, and HR Matka follows 50 minutes later. During the quiet period before Alwar, the today cell may be the only empty one in the evening, and it is marked with the bouncing WAIT word until publication. When an editor saves the result, the number shows up in bold in the hero panel, in the table and in the notice bar. Remember that all times here are Indian Standard Time.",
f"Use the tables to read Alwar by date. The first covers the month you picked, and the second covers the month before it, so even at the start of a month you can see a full run of entries. The latest number on record is {LATEST}, published for {LDATE}. A dash is shown wherever no entry exists, and nothing is ever filled in automatically. For quick comparisons, open the chart page, where every game appears on the same calendar, or use the JSON link to bring this month’s Alwar entries into your own spreadsheet or tool."
)

G['hr-matka'] = (
"HR Matka is the tenth game on the daily board, placed in the middle of the evening. The name appears here exactly as it is written in the site’s catalogue, and the page handles it the way it handles every other game: it stores dated numbers that editors publish and presents them clearly. Nothing here predicts a future result, and the site has no connection to betting, so the page should be treated as reference material only.",
f"HR Matka is listed at {T}. Alwar comes 50 minutes earlier, and Gaziabad follows 65 minutes later, so the game sits comfortably in the centre of the night block. Before the number is published, its today cell displays WAIT with a springy bounce, and the same status runs through the notice bar at the top of the site. When the result is saved, the value replaces the waiting word in every place that shows it. The clock behind these changes follows Indian Standard Time, which means the page stays accurate for visitors anywhere.",
f"Below the quick answer you will find the monthly tables, newest month first and the previous month second. The most recent published figure for HR Matka is {LATEST}, which is dated {LDATE}. Numbers are shown in bold for readability, and a dash simply marks a missing entry. If you are researching several games, the chart page gathers them in one grid, and the JSON link provides this game’s month as structured data, which is useful when you want to keep your own archive or build a small tool."
)

G['gaziabad'] = (
"Gaziabad is the eleventh game in the lineup, and the spelling on this page follows the one used in the site’s catalogue. It is a late-evening entry, and its page gathers the published numbers into an easy-to-scan monthly record. The page is informational. It will not forecast a number, it does not rank or recommend anything, and it does not link to any service where money could be staked.",
f"Gaziabad is scheduled for {T}. HR Matka precedes it by 65 minutes, and Huda City follows after a longer gap of 80 minutes, so the page often gets a quiet period after the number is published. Until the entry is saved, the today cell shows the bouncing WAIT word, which reassures you that the game has simply not been announced yet. Once the number goes live, it appears in bold on the page, in the homepage cards and in the scrolling notice bar. Times are in Indian Standard Time throughout the site.",
f"The record is laid out as two tables: the month you selected and the month before it. Gaziabad’s latest published number is {LATEST} for {LDATE}. Where a date has no entry, a dash appears, and it never means zero. If you need to look further back, change the month in the selector, and if you want to see how Gaziabad compares with the games on either side, open the full chart. The JSON link next to the quick answer returns the same entries, so assistants and scripts can cite exactly what visitors see."
)

G['huda-city'] = (
"Huda City is the twelfth game on the board and one of the late-night entries. This page keeps its record tidy: a status summary at the top, followed by dated tables for two months. That is all it does. It is not a forecasting tool, it does not suggest numbers, and it has no link to any betting or payment service, so it should be used only as a dated reference.",
f"Huda City is listed at {T}. Gaziabad comes 80 minutes before it, and Gali follows after 45 minutes, so the late evening moves quickly. If you check the page before the number is published, the today cell shows WAIT, bouncing in amber on light backgrounds and in yellow on dark ones. After an editor saves the entry, the number replaces it. Because the slot falls close to bedtime for many people, the status in the notice bar is useful: it lets you check from any page without opening this one. All times follow Indian Standard Time.",
f"To read the history, use the two monthly tables. The first is the month in the selector and the second is the month before it. The latest number on record is {LATEST}, dated {LDATE}. Dates with nothing published show a dash, and the page does not fill gaps or estimate values. You can move to earlier months at any time, compare this game against others on the chart page, or download the same entries through the JSON link for use in your own notes or software."
)

G['gali'] = (
"Gali is the thirteenth game in the daily order and the last one scheduled before midnight. Its page is a clear, dated log of the numbers that editors have published, with a short summary at the top. The content is purely informational. The site does not predict results, does not give tips and does not provide any means to play, so nothing here should be taken as encouragement to wager.",
f"The listed time for Gali is {T}. Huda City comes 45 minutes earlier, and then there is the longest pause in the whole schedule, because Disawar is not listed until 1:00 AM IST, 95 minutes later. For that reason Gali is often the last number people check before the day ends. Until it has been announced, the today cell shows the animated WAIT word, and the notice bar carries the same status. When the editor publishes, the number appears in bold. Remember that the site uses Indian Standard Time for every date and time.",
f"Read the record through the two monthly tables: the selected month first, the previous month second. The latest published Gali number is {LATEST}, recorded for {LDATE}. A dash is a date without an entry. Since the game runs late in the day, check the date column carefully when you compare entries around midnight. To review other periods, use the month selector, and to see Gali next to the rest of the lineup, open the chart page. The JSON link gives developers the same entries in a structured format."
)

G['disawer'] = (
"Disawar, written Disawer in the site’s catalogue, is the last game in the daily lineup and the only one timed after midnight. Its page works as a simple dated archive, with a short status summary and two monthly tables. Everything on it is informational. The site does not forecast numbers, does not offer tips and is not connected to betting or payments, so treat the page as reference material and nothing more.",
f"Disawer is listed at {T}, 95 minutes after Gali and well into the early hours. Because the slot falls just after midnight, the calendar date matters: always read the date beside each entry rather than assuming it belongs to the evening before. Until the number is published, the today cell shows the bouncing WAIT word, and the same state appears in the scrolling notice bar. When the editor saves the result, the number appears in bold in all places. The site keeps every date and time in Indian Standard Time.",
f"The two monthly tables below the summary cover the selected month and the month before it. The latest published figure is {LATEST}, recorded for {LDATE}. Gaps appear as dashes and are never filled with estimates. Use the month selector to look at earlier periods, or open the full chart to compare Disawer with the other thirteen games on one calendar. If you work with data, the JSON link beside the quick answer provides this month’s entries in a structured form, so your own tools can match what the page shows."
)

FULL = '[khaiwal_game field="latest_full"]'
PAIR = re.compile(r'\[khaiwal_game field="latest"\][^\[]{0,30}\[khaiwal_game field="latest_date"\]')

NOTE = ("This page is for information only. {name} results are published for reference, are never predicted, and are not an invitation to wager. "
        "Playing for money in games of chance is restricted or illegal in much of India, and it carries real financial risk. If gambling is causing you or someone you know stress, please reach out to a trusted person or a local support service.")

def blocks(slug, name, hindi, time):
    intro, timing, chart = G[slug]
    chart = PAIR.sub(FULL, chart)
    def h(level, text): return f'<!-- wp:heading {{"level":{level}}} -->\n<h{level} class="wp-block-heading">{text}</h{level}>\n<!-- /wp:heading -->\n\n'
    def p(text): return f'<!-- wp:paragraph -->\n<p>{text}</p>\n<!-- /wp:paragraph -->\n\n'
    out = h(2, f'About {name} ({hindi})') + p(intro)
    out += h(3, f'When {name} is announced') + p(timing)
    out += h(3, f'How to read the {name} record') + p(chart)
    out += h(3, 'Please read this') + p(NOTE.format(name=name))
    return out.strip() + '\n'

def words(html, time):
    t = re.sub(r'<!--.*?-->', ' ', html, flags=re.S)
    t = re.sub(r'<[^>]+>', ' ', t)
    t = t.replace(T, time + ' IST').replace(FULL, '42 (for 2 Nov 2026)').replace(LATEST, '42').replace(LDATE, '2 Nov 2026')
    return len(t.split())

def main():
    root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    games = json.load(open(os.path.join(root, 'data/games.json'), encoding='utf-8'))['games']
    out, ok = {}, True
    for g in games:
        if g['slug'] not in G:
            print('MISSING', g['slug']); ok = False; continue
        html = blocks(g['slug'], g['name'], g['hindi'], g['time'])
        n = words(html, g['time'])
        print(f"{g['slug']:14s} {n} words", '' if 300 <= n <= 400 else '  <-- OUT OF RANGE')
        ok = ok and 300 <= n <= 400
        out[g['slug']] = html
    json.dump(out, open(os.path.join(root, 'data/game-content.json'), 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
    sys.exit(0 if ok else 1)

main()
