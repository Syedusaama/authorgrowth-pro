/* Browser API doubles for countdown and manual slider. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const future = {dataset:{agpLaunch:String(Date.now()/1000+90061)},textContent:''};
const past = {dataset:{agpLaunch:'1'},textContent:''};
const clicks = [];
const previous = {hasAttribute:()=>true,addEventListener:(type,fn)=>clicks.push(fn)};
const next = {hasAttribute:()=>false,addEventListener:(type,fn)=>clicks.push(fn)};
const controls = {dataset:{agpControls:'track'},hidden:true,querySelectorAll:()=>[previous,next]};
const track = {clientWidth:320,scrollBy:options=>track.last=options};
let intervals = 0;
vm.runInNewContext(fs.readFileSync('authorgrowth-pro/assets/release.js','utf8'), {
 document:{querySelectorAll:selector=>selector==='[data-agp-launch]'?[future,past]:[controls],getElementById:()=>track},
 Date, Number, Math, setInterval:()=>++intervals, clearInterval:()=>{},window:{matchMedia:()=>({matches:true})}
});
assert.match(future.textContent,/1d 1h 1m/);
assert.match(past.textContent,/Launch time reached/);
assert.equal(intervals,1);
assert.equal(controls.hidden,false);
clicks[0]();assert.equal(track.last.left,-320);
clicks[1]();assert.equal(track.last.left,320);
assert.equal(track.last.behavior,'auto');
console.log('PASS: 7 JavaScript assertions');
