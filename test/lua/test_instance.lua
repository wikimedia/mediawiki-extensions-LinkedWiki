-- @copyright (c) 2019 Bourdercloud.com
-- @author Karima Rafes <karima.rafes@bordercloud.com>
-- @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
-- @license CC-by-sa V4.0
--
--  Last version: https://github.com/BorderCloud/LinkedWiki
--
--
-- This work is licensed under the Creative Commons
-- Attribution-NonCommercial-ShareAlike 3.0
-- Unported License. To view a copy of this license,
-- visit http://creativecommons.org/licenses/by-nc-sa/3.0/
-- or send a letter to Creative Commons,
-- 171 Second Street, Suite 300, San Francisco,
-- California, 94105, USA.

--[[
-- Debug console

mw.log(p.tests() )
]]

local p = {}

function p.checkLitteral(query, litteral)
    local result = ''
    if string.match(query, litteral) then
        result = "OK"
    else
        result = "KO"
    end
    return result
end


function p.checkString(val1, val2)
    local result = ''
    if (val1 ==  nil or val2 ==  nil) then
        result = "KO"
    else
        if val1 ==  val2 then
            result = "OK"
        else
            result = "KO"
        end
    end
    return result
end
function p.checkNumber(val1, val2)
    local result = ''
    if (val1 ==  nil or val2 ==  nil) then
        result = "KO"
    else
        if tonumber(val1) ==  tonumber(val2) then
            result = "OK"
        else
            result = "KO"
        end
    end
    return result
end
function p.checkBool(val1, val2)
    local result = ''
    if (val1 ==  nil or val2 ==  nil) then
        result = "KO"
    else
        if val1 ==  val2 then
            result = "OK"
        else
            result = "KO"
        end
    end
    return result
end


function p.tests(f)
    local html = ""
    local result = ""
    local linkedwiki = require 'linkedwiki'

    local wd = "http://www.wikidata.org/entity/"
    local subject1 = wd.."Q1"

    local wd = "http://www.wikidata.org/entity/"
    local subject2 = wd.."Q2"

    local configTest = 'http://database-test'
    local configWikidata = "http://www.wikidata.org"

--    for i,v in pairs(linkedwiki) do
--       mw.log(i)
--    end

    -- Config by default : Wikidata and taglang : en
    local ObjWikidata =  linkedwiki.new(subject1)

    -- Config by default : Wikidata but taglang : fr
    local ObjWikidataFr =  linkedwiki.new(subject1,nil,"fr")

    --Lang by default : en
    local objTest = linkedwiki.new(subject2,configTest)

    -- config with another taglang
    local objTestFr = linkedwiki.new(subject2,configTest,"fr")

    html = html .."----------------------------------------------------------------------------" ..'\n'
    html = html .."TEST : Subject " ..'\n'

    result = objTest:getSubject()
    html = html .."RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,subject2) ..'\n'

    result = ObjWikidata:getSubject()
    html = html .."RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,subject1) ..'\n'

    html = html .."----------------------------------------------------------------------------" ..'\n'
    html = html .."TEST : Config " ..'\n'

    result = objTest:getConfig()
    html = html .."RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,configTest) ..'\n'

    result = ObjWikidata:getConfig()
    html = html .."RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,configWikidata) ..'\n'

    result = objTestFr:getConfig()
    html = html .."RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,configTest) ..'\n'

    result = ObjWikidataFr:getConfig()
    html = html .."RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,configWikidata) ..'\n'


    html = html .."----------------------------------------------------------------------------" ..'\n'
    html = html .."TEST : lang " ..'\n'

    result = objTest:getLang()
    html = html .."RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,"en") ..'\n'

    result = ObjWikidata:getLang()
    html = html .."RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,"en") ..'\n'


    result = objTestFr:getLang()
    html = html .."RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,"fr") ..'\n'

    result = ObjWikidataFr:getLang()
    html = html .."RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,"fr") ..'\n'


    html = html .."----------------------------------------------------------------------------" ..'\n'
    html = html .."TEST : debug " ..'\n'

    result = objTest:isDebug()
    html = html .."RESULT BEGIN : "..'\n' ..tostring(result) ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkBool(result,false) ..'\n'

    result = ObjWikidata:isDebug()
    html = html .."RESULT BEGIN : "..'\n' ..tostring(result) ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkBool(result,false) ..'\n'


    result = objTestFr:setDebug(true)
    result = objTest:isDebug()
    html = html .."RESULT BEGIN : "..'\n' ..tostring(result) ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkBool(result,false) ..'\n'

    result = ObjWikidata:isDebug()
    html = html .."RESULT BEGIN : "..'\n' ..tostring(result) ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkBool(result,false) ..'\n'

    return "<nowiki><pre>"..mw.text.encode( html).."</pre></nowiki>"
end

return p
