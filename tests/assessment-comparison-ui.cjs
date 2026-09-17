// Runs the actual inline comparison renderer against a small DOM fixture.
// No npm dependencies required. Run: node tests/assessment-comparison-ui.cjs
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const view = fs.readFileSync(require('node:path').join(__dirname, '../resources/views/recruitment/assessment-insights/index.blade.php'), 'utf8');
const scripts = [...view.matchAll(/<script>([\s\S]*?)<\/script>/g)].map(m => m[1]);
const metrics = {vacancy_qualification_match:{score:100,purpose:'fit',coverage:100,primary:'4/4 required qualifications matched · 1/1 preferred/nice-to-have evidenced',secondary:'0 required not met · 0 required not evidenced · 0 required verify',matched:5,not_matched:0,not_evidenced:0,needs_verification:0,required_total:4,required_matched:4,required_not_matched:0,required_not_evidenced:0,required_needs_verification:0,preferred_total:1,preferred_matched:1,assessed_match_rate:100}, relevant_experience:{score:80, purpose:'fit', coverage:100, primary:'IT support', matched:null}, education_certifications:{score:null, purpose:'context', coverage:100, primary:'BSIT', matched:null}};
const candidates = {
  1:{id:1,name:'<img src=x onerror=alert(1)>',reference:'APP-1',fit:'Good Fit',overall:80.1,reliability:80,metrics},
  2:{id:2,name:'Candidate B',reference:'APP-2',fit:'Good Fit',overall:80.2,reliability:80,metrics:{...metrics,relevant_experience:{...metrics.relevant_experience,score:null}}}
};
const source = scripts[0].replace('@json($comparisonData)', JSON.stringify(candidates))
  .replace('@json(array_keys($comparisonOrder))', JSON.stringify(Object.keys(metrics)))
  .replace('@json($comparisonOrder)', JSON.stringify({vacancy_qualification_match:['Qualifications','Required baseline'],relevant_experience:['Experience','Evidence'],education_certifications:['Education','Context']}));
const elements = {};
function el(id){return elements[id] ||= {value:'',innerHTML:'',events:{},classList:{set:new Set(),add(x){this.set.add(x)},remove(x){this.set.delete(x)},toggle(x,on){on?this.add(x):this.remove(x)}},addEventListener(name,fn){this.events[name]=fn}}}
const document={getElementById:el};
vm.runInNewContext(source,{document});
assert.equal(el('comparisonResults').classList.set.has('d-none'),true,'empty selection hides results');
el('compareA').value='1';el('compareB').value='2';el('runComparison').events.click();
assert.equal(el('comparisonResults').classList.set.has('d-none'),false);
assert.match(el('comparisonBody').innerHTML,/80\.1%/,'decimal scores remain visible');
assert.match(el('comparisonBody').innerHTML,/Higher \+0\.1/,'small advantages are not rounded to zero');
assert.match(el('comparisonBody').innerHTML,/Not comparable/,'missing evidence is not called a tie');
assert.match(el('comparisonBody').innerHTML,/N\/A/);
assert.match(el('comparisonBody').innerHTML,/Required: 4\/4 met/,'required qualification counts render separately');
assert.doesNotMatch(el('comparisonBody').innerHTML,/5\/5 matched/,'required and optional rows are not collapsed into a fake hard-requirement total');
assert.doesNotMatch(el('compareHeaderA').innerHTML,/<img /,'candidate name is escaped');
assert.match(el('compareHeaderA').innerHTML,/&lt;img /);
el('compareB').value='1';el('compareB').events.change();
assert.equal(el('comparisonBody').innerHTML,'','same candidate clears stale comparison');
assert.equal(el('comparisonSummary').innerHTML,'');
assert.equal(el('sameCandidateWarning').classList.set.has('d-none'),false);
for(const s of scripts.slice(1))new vm.Script(s);
console.log('PASS: 13 comparison renderer assertions; both inline scripts parse.');
