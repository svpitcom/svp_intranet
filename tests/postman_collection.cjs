const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const path = require('node:path');
const root = path.join(__dirname, '../docs/postman');
const collection = JSON.parse(fs.readFileSync(path.join(root, 'SVP_Intranet.postman_collection.json'), 'utf8'));
const environment = JSON.parse(fs.readFileSync(path.join(root, 'SVP_Local.postman_environment.json'), 'utf8'));
let scripts = 0;
function visit(item) {
  for (const e of item.event || []) { new vm.Script(e.script.exec.join('\n')); scripts++; }
  for (const child of item.item || []) visit(child);
}
visit(collection);
const source = collection.event.find(e => e.listen === 'prerequest').script.exec.join('\n');
function run(name, method, url, overrides = {}) {
  const values = Object.assign(Object.fromEntries(environment.values.map(v => [v.key, v.value])), overrides);
  let skipped = false;
  const stop = {};
  const pm = {
    info: {requestName: name}, request: {method, url: {toString: () => url}},
    environment: {get: k => values[k], set: (k,v) => { values[k] = v; }},
    variables: {replaceIn: s => s.replace(/\{\{([^}]+)\}\}/g, (match,k) => values[k] === undefined ? match : values[k])},
    execution: {skipRequest: () => { skipped = true; throw stop; }}
  };
  try { vm.runInNewContext(source, {pm, console: {warn(){}}}); } catch (e) { if (e !== stop) throw e; }
  return {skipped, values};
}
assert.equal(run('POST /devices/create','POST','{{base_url}}/devices/create').skipped, true);
assert.equal(run('POST /login','POST','{{base_url}}/login').skipped, true);
assert.equal(run('POST /login','POST','{{base_url}}/login',{username:'test',password:'test',csrf_token:'test'}).skipped, false);
const allowed = run('POST /devices/create','POST','{{base_url}}/devices/create',{allow_write_request:'POST /devices/create',csrf_token:'test'});
assert.equal(allowed.skipped, false);
assert.equal(allowed.values.allow_write_request, '');
assert.equal(run('GET /users/{id}/edit','GET','{{base_url}}/users/{{user_id}}/edit').skipped, true);
assert.equal(run('GET /users/{id}/edit','GET','{{base_url}}/users/{{user_id}}/edit',{user_id:'123'}).skipped, false);
assert.equal(run('POST /users/{id}/delete','POST','{{base_url}}/users/123/delete',{allow_write_request:'POST /devices/create',csrf_token:'test'}).skipped, true);
for (const key of ['username','password','csrf_token','new_user_password']) assert.equal(environment.values.find(v => v.key === key).value, '');
console.log(`PASS: ${scripts} Postman scripts parse; offline safety/login/ID checks pass; credentials empty. No HTTP requests sent.`);
