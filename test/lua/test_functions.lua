-- @copyright (c) 2016 Bourdercloud.com
-- @author Karima Rafes <karima.rafes@bordercloud.com>
-- @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
-- @license CC-by-nc-sa V3.0
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

-- @copyright (c) 2016 Bourdercloud.com
-- @author Karima Rafes <karima.rafes@bordercloud.com>
-- @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
-- @license CC-by-nc-sa V3.0
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

frame = mw.getCurrentFrame() -- Get a frame object
newFrame = frame:newChild{ -- Get one with args
	title = 'test' ,
 args = {
 iri = 'http://daap.eu/wiki/Lip(Sys)2/RamanEvolution_Spectrometer'
    }
}

mw.log(p.tests(newFrame) )
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
        result = "KO1"
    else
        if val1 ==  val2 then
            result = "OK"
        else
            result = "KO2"
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


function p.tests(f)

    local linkedwiki = require 'linkedwiki'
    local endpoint = 'http://database-test:8890/sparql'
    local config = 'http://database-test/data'


    local xsd = 'http://www.w3.org/2001/XMLSchema#'
    local rdfs = 'http://www.w3.org/2000/01/rdf-schema#'
    local wdt = 'http://www.wikidata.org/prop/direct/'

    local pr = 'http://database-test/TestFunction:'

    local html = '== TESTS =='.. '\n'

    --linkedwiki.setDebug(true)

    local subject = f.args.iri or linkedwiki.getCurrentIRI();
    html = html .."TEST : f.args.iri or linkedwiki.getCurrentIRI()" .. '\n'
    html = html .."RESULT : " .. subject .. '\n'

local subject = linkedwiki.getCurrentIRI()


local objTest = linkedwiki.new(subject,config)
    local pTObjTemp1 = mw.title.new("Title1"):fullUrl()
    local pTObjTemp2 = mw.title.new("Title2"):fullUrl()

    local ObjTemp1 = linkedwiki.new(pTObjTemp1);
    local ObjTemp2 = linkedwiki.new(pTObjTemp2);



    mw.log(objTest:removeSubject())

    html = html .."----------------------------------------------------------------------------" ..'\n'
    html = html .."TEST : checkValue" ..'\n'
    --    function linkedwiki.checkValue(property, valueInWiki)
    local valueInWiki = 0
    local result = ""
    local pT =""

    pT = pr..'1'

    valueInWiki = "1"
    result = objTest:checkValue(pT,valueInWiki)
    html = html .."1 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_new_value">1</div>') ..'\n'

    mw.log(objTest:addPropertyWithLitteral(pT,1))

    valueInWiki = "1"
    result = objTest:checkValue(pT,valueInWiki)
    html = html .."2 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_value_equal">1</div>') ..'\n'

    valueInWiki = "2"
    result =objTest:checkValue(pT,valueInWiki)
    html = html .."3 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_new_value linkedwiki_tooltip" data-toggle="tooltip" data-placement="bottom" title="Currently in DB : 1">2</div>') ..'\n'

    html = html .."----------------------------------------------------------------------------" ..'\n'
    html = html .."TEST : checkString" ..'\n'
--    function objTest:checkString(property, valueInWiki, tagLang)

    pT = pr..'2'
    valueInWiki = "test"
    result = objTest:checkString(pT,valueInWiki)
    html = html .."4 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_new_value">test</div>') ..'\n'

    --mw.log(objTest:addPropertyWithLitteral(pT,"test"))
    mw.log(objTest:addPropertyString(pT,"test"))

    valueInWiki = "test"
    result = objTest:checkString(pT,valueInWiki)
    html = html .."5 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_value_equal">test</div>') ..'\n'

    valueInWiki = "testDifferent"
    result = objTest:checkString(pT,valueInWiki)
    html = html .."6 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_new_value linkedwiki_tooltip" data-toggle="tooltip" data-placement="bottom" title="Currently in DB : test">testDifferent</div>') ..'\n'


    --mw.log(objTest:addPropertyWithLitteral(pT,"testfr",nil,"fr"))
    mw.log(objTest:addPropertyString(pT,"testfr","fr"))

    valueInWiki = "testfr"
    result = objTest:checkString(pT,valueInWiki,"fr")
    html = html .."7 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" .. '\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_value_equal">testfr</div>') ..'\n'

    valueInWiki = "testfrDifferent"
    result = objTest:checkString(pT,valueInWiki,"fr")
    html = html .."8 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_new_value linkedwiki_tooltip" data-toggle="tooltip" data-placement="bottom" title="Currently in DB : testfr">testfrDifferent</div>') ..'\n'


    html = html .."----------------------------------------------------------------------------" ..'\n'
    html = html .."TEST : checkLabelOfInternLink" ..'\n'
    --  function objTest:checkLabelOfInternLink(link, propertyOfLabel, labelInWiki, tagLang)

    pT = pr..'3'
    valueInWiki = "test"
    local link = "http://example.com/link"
    result = objTest:checkLabelOfInternLink(link,pT,valueInWiki)
    html = html .."9 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_new_value"><span class="plainlinks">[http://example.com/link test]</span></div>') ..'\n'

    mw.log(objTest:addPropertyWithLitteral(pT,"test"))

    valueInWiki = "test"
    result = objTest:checkLabelOfInternLink(link,pT,valueInWiki)
    html = html .."10 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_value_equal"><span class="plainlinks">[http://example.com/link test]</span></div>') ..'\n'

    valueInWiki = "test2"
    result = objTest:checkLabelOfInternLink(link,pT,valueInWiki)
    html = html .."11 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_new_value linkedwiki_tooltip" data-toggle="tooltip" data-placement="bottom" title="Currently in DB : test"><span class="plainlinks">[http://example.com/link test2]</span></div>') ..'\n'

    html = html .."----------------------------------------------------------------------------" ..'\n'
    html = html .."TEST : checkIriOfExternLink" ..'\n'
    -- function objTest:checkIriOfExternLink(labelOfExternLink, propertyOfExternLink, externLinkInWiki)

    pT = pr..'4'
    valueInWiki = "http://example.com/test"
    result = objTest:checkIriOfExternLink("test",pT,valueInWiki)
    html = html .."12 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_new_value">[http://example.com/test test]</div>') ..'\n'

    mw.log(objTest:addPropertyWithIri(pT,"http://example.com/test"))

    valueInWiki = "http://example.com/test"
    result = objTest:checkIriOfExternLink("test",pT,valueInWiki)
    html = html .."13 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_value_equal">[http://example.com/test test]</div>') ..'\n'

    valueInWiki = "http://example.com/test2"
    result = objTest:checkIriOfExternLink("test",pT,valueInWiki)
    html = html .."14 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_new_value linkedwiki_tooltip" data-toggle="tooltip" data-placement="bottom" title="Currently in DB : http://example.com/test">[http://example.com/test2 test]</div>') ..'\n'

    html = html .."----------------------------------------------------------------------------" ..'\n'
    html = html .."TEST : checkImage" ..'\n'
--    function objTest:checkImage(property, valueInWiki, width, height)

    pT = pr..'5'
    valueInWiki = "http://example.com/test.png"
    result = objTest:checkImage(pT,valueInWiki)
    html = html .."15 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_new_value"><img src="http://example.com/test.png" /></div>') ..'\n'

    mw.log(objTest:addPropertyWithIri(pT,"http://example.com/test.png"))

    valueInWiki = "http://example.com/test.png"
    result = objTest:checkImage(pT,valueInWiki)
    html = html .."16 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_value_equal"><img src="http://example.com/test.png" /></div>') ..'\n'

    valueInWiki = "http://example.com/test2.png"
    result = objTest:checkImage(pT,valueInWiki)
    html = html .."17 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_new_value linkedwiki_tooltip" data-toggle="tooltip" data-placement="bottom" title="Currently in DB : http://example.com/test.png"><img src="http://example.com/test2.png" /></div>') ..'\n'

    html = html .."----------------------------------------------------------------------------" ..'\n'
    html = html .."TEST : checkTitle" ..'\n'
    -- objTest:checkTitle(property, labelInWiki, tagLang)

    --objTest:setDebug(true)
    pT = pr..'6'
    local pTObjTemp1 = mw.title.new("Title1"):fullUrl()
    local pTObjTemp2 = mw.title.new("Title2"):fullUrl()

    local ObjTemp1 = linkedwiki.new(pTObjTemp1,config);
    local ObjTemp2 = linkedwiki.new(pTObjTemp2,config);

--    mw.log(objTest:getConfig())
--    mw.log(ObjTemp1:getConfig())
--    mw.log(ObjTemp2:getConfig())
    mw.log(objTest:removeSubject())
    mw.log(ObjTemp1:removeSubject())
    mw.log(ObjTemp2:removeSubject())

    valueInWiki = ""
    --0 in DB
    result = objTest:checkTitle(pT,valueInWiki)
    html = html .."18 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'') ..'\n'

    valueInWiki = "Title1;Title2"
    --0 in DB
    result = objTest:checkTitle(pT,valueInWiki)
    html = html .."19 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_new_value">[[Title1]], [[Title2]]</div>') ..'\n'

    valueInWiki = " Title1;Title2 "
    --"Title1" in DB
    -- current - pT -> pTObj
    -- pTObj - rdfs:label -> "Title1"
    mw.log(objTest:addPropertyWithIri(pT, pTObjTemp1))
    --mw.log(ObjTemp1:addPropertyWithLitteral(rdfs.."label","Title1"))
    mw.log(ObjTemp1:addPropertyString(rdfs.."label","Title1"))
   -- mw.log(linkedwiki.getLastQuery())
    result = objTest:checkTitle(pT,valueInWiki)
    html = html .."20 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_new_value linkedwiki_tooltip" data-toggle="tooltip" data-placement="bottom" title="Currently in DB : Title1">[[Title2]], <span class="plainlinks">[http://wiki.serverdev-mediawiki-v1/index.php/Title1 Title1]</span></div>') ..'\n'

    valueInWiki = "Title1 ; Title2"
    --"Title1;Title2" in DB
    --"Title1" in DB
    -- current - pT -> pTObj2
    -- pTObj2 - rdfs:label -> "Title2"
    mw.log(objTest:addPropertyWithIri(pT,pTObjTemp2))
    --mw.log(objTest:addPropertyWithLitteral(rdfs.."label","Title2",nil,nil,pTObj2))
    mw.log(ObjTemp2:addPropertyString(rdfs.."label","Title2"))
    result = objTest:checkTitle(pT,valueInWiki)
    html = html .."21 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_value_equal"><span class="plainlinks">[http://wiki.serverdev-mediawiki-v1/index.php/Title1 Title1]</span>, <span class="plainlinks">[http://wiki.serverdev-mediawiki-v1/index.php/Title2 Title2]</span></div>') ..'\n'

    valueInWiki = ""
    --"Title1;Title2" in DB
    result = objTest:checkTitle(pT,valueInWiki)
    html = html .."22 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_value_equal"><span class="plainlinks">[http://wiki.serverdev-mediawiki-v1/index.php/Title1 Title1]</span>, <span class="plainlinks">[http://wiki.serverdev-mediawiki-v1/index.php/Title2 Title2]</span></div>') ..'\n'


    html = html .."----------------------------------------------------------------------------" ..'\n'
    html = html .."TEST : checkUser" ..'\n'
--    function objTest:checkUser(property, valueInWiki)


    mw.log(objTest:removeSubject())

    pT = pr..'7'

    local pTObjTemp1 = mw.title.new("User:Firstname Surename1"):fullUrl()
    local pTObjTemp2 = mw.title.new("User:Firstname Surename2"):fullUrl()

    local ObjTemp1 = linkedwiki.new(pTObjTemp1,config)
    local ObjTemp2 = linkedwiki.new(pTObjTemp2,config)

    mw.log(objTest:removeSubject())
    mw.log(ObjTemp1:removeSubject())
    mw.log(ObjTemp2:removeSubject())

    valueInWiki = ""
    --0 in DB
    result = objTest:checkUser(pT,valueInWiki)
    html = html .."23 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'') ..'\n'

    valueInWiki = "User1"
    --0 in DB
    result = objTest:checkUser(pT,valueInWiki)
    html = html .."24 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_new_value">[[User:User1|User1]]</div>') ..'\n'

    valueInWiki = "User1;User2"
    --0 in DB
    result = objTest:checkUser(pT,valueInWiki)
    html = html .."25 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_new_value">[[User:User1|User1]], [[User:User2|User2]]</div>') ..'\n'

    valueInWiki = ""
    --"User:Firstname Surename1" in DB
    -- current - pT -> pTObj
    -- pTObj - rdfs:label -> "User:Firstname Surename1"
    mw.log(objTest:addPropertyWithIri(pT, pTObjTemp1))
    mw.log(ObjTemp1:addPropertyWithLitteral(rdfs.."label","Firstname Surename1"))
    result = objTest:checkUser(pT,valueInWiki)
    html = html .."26 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_value_equal"><span class="plainlinks">[http://wiki.serverdev-mediawiki-v1/index.php/User:Firstname_Surename1 Firstname Surename1]</span></div>') ..'\n'

    valueInWiki  = "Firstname Surename1; Firstname Surename2"
    result = objTest:checkUser(pT,valueInWiki)
    html = html .."27 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_new_value linkedwiki_tooltip" data-toggle="tooltip" data-placement="bottom" title="Currently in DB : Firstname Surename1">[[User:Firstname Surename2|Firstname Surename2]], <span class="plainlinks">[http://wiki.serverdev-mediawiki-v1/index.php/User:Firstname_Surename1 Firstname Surename1]</span></div>') ..'\n'

    valueInWiki  = "Firstname Surename1; Firstname Surename2"
    --"User:Firstname Surename1;User:Firstname Surename2" in DB
    --"User:Firstname Surename2" in DB
    -- current - pT -> pTObj2
    -- pTObj2 - rdfs:label -> "User:Firstname Surename2"
    mw.log(objTest:addPropertyWithIri(pT,pTObjTemp2))
    mw.log(ObjTemp2:addPropertyWithLitteral(rdfs.."label","Firstname Surename2"))
    result = objTest:checkUser(pT,valueInWiki)
    html = html .."28 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_value_equal"><span class="plainlinks">[http://wiki.serverdev-mediawiki-v1/index.php/User:Firstname_Surename1 Firstname Surename1]</span>, <span class="plainlinks">[http://wiki.serverdev-mediawiki-v1/index.php/User:Firstname_Surename2 Firstname Surename2]</span></div>') ..'\n'

    valueInWiki  = "Firstname Surename1; Firstname Surename2"
    --"User:Firstname Surename1;User:Firstname Surename2" in DB
    --"mailto:Firstname_Surename2@example.com" ADD in DB
    -- current - pT -> pTObj2
    -- pTObj2 - http://www.w3.org/2006/vcard/ns#email -> "mailto:Firstname_Surename2@example.com"
    mw.log(ObjTemp2:addPropertyWithIri("http://www.w3.org/2006/vcard/ns#email","mailto:Firstname_Surename2@example.com"))
    result = objTest:checkUser(pT,valueInWiki)
    html = html .."29 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_value_equal"><span class="plainlinks">[http://wiki.serverdev-mediawiki-v1/index.php/User:Firstname_Surename1 Firstname Surename1]</span>, <span class="plainlinks">[http://wiki.serverdev-mediawiki-v1/index.php/User:Firstname_Surename2 Firstname Surename2]</span><sub><span class="plainlinks" style="font-size: large;">[mailto:Firstname_Surename2@example.com &#9993;]</span></sub></div>') ..'\n'

   --html = html .."RESULT BEGIN : "..'\n' ..ObjTemp2:getValue("http://www.w3.org/2006/vcard/ns#email") ..'\n'.."END" ..'\n'

    valueInWiki  = "Firstname Surename1; Firstname Surename2"
    --"User:Firstname Surename1;User:Firstname Surename2" in DB
    --"mailto:Firstname_Surename2@example.com" ADD in DB
    -- current - pT -> pTObj2
    -- pTObj2 - http://www.w3.org/2006/vcard/ns#email -> "mailto:Firstname_Surename2@example.com"
    mw.log(ObjTemp2:addPropertyWithIri("http://www.w3.org/2006/vcard/ns#email","mailto:Firstname_Surename2Bis@example.com"))
    result = objTest:checkUser(pT,valueInWiki)
    html = html .."30 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_value_equal"><span class="plainlinks">[http://wiki.serverdev-mediawiki-v1/index.php/User:Firstname_Surename1 Firstname Surename1]</span>, <span class="plainlinks">[http://wiki.serverdev-mediawiki-v1/index.php/User:Firstname_Surename2 Firstname Surename2]</span><sub><span class="plainlinks" style="font-size: large;">[mailto:Firstname_Surename2@example.com &#9993;]</span></sub><sub><span class="plainlinks" style="font-size: large;">[mailto:Firstname_Surename2Bis@example.com &#9993;]</span></sub></div>') ..'\n'

    valueInWiki = ""
    --"Title1;Title2" in DB
    result = objTest:checkUser(pT,valueInWiki)
    html = html .."31 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_value_equal"><span class="plainlinks">[http://wiki.serverdev-mediawiki-v1/index.php/User:Firstname_Surename1 Firstname Surename1]</span>, <span class="plainlinks">[http://wiki.serverdev-mediawiki-v1/index.php/User:Firstname_Surename2 Firstname Surename2]</span><sub><span class="plainlinks" style="font-size: large;">[mailto:Firstname_Surename2@example.com &#9993;]</span></sub><sub><span class="plainlinks" style="font-size: large;">[mailto:Firstname_Surename2Bis@example.com &#9993;]</span></sub></div>') ..'\n'


    html = html .."----------------------------------------------------------------------------" ..'\n'

    html = html .."TEST : checkItem" ..'\n'
    --    function objTest:checkItem(property, valueInWiki, tagLang)

    mw.log(objTest:removeSubject())
   --objTest:setDebug(true)

    pT = pr..'8'
    local wd = "http://www.wikidata.org/entity/"
    local pTObjTemp1 = wd.."Q1"
    local pTObjTemp2 = wd.."Q2"
    local ObjTemp1 = linkedwiki.new(pTObjTemp1,config)
    local ObjTemp2 = linkedwiki.new(pTObjTemp2,config)

    mw.log(objTest:removeSubject())
    mw.log(ObjTemp1:removeSubject())
    mw.log(ObjTemp2:removeSubject())

    valueInWiki = ""
    --0 in DB
    result = objTest:checkItem(pT,valueInWiki)
    html = html .."32 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'') ..'\n'

    valueInWiki = "Q1 ; Q2 "
    --0 in DB
    result = objTest:checkItem(pT,valueInWiki)
    html = html .."33 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'

  html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_new_value"><span class="plainlinks">[https://www.wikidata.org/wiki/Special:GoToLinkedPage/enwiki/Q2 Earth]</span><span class="plainlinks"><small>([http://www.wikidata.org/entity/Q2 Q2])</small></span>, <span class="plainlinks">[https://www.wikidata.org/wiki/Special:GoToLinkedPage/enwiki/Q1 universe]</span><span class="plainlinks"><small>([http://www.wikidata.org/entity/Q1 Q1])</small></span></div>') ..'\n'


    valueInWiki =  ""
    --"universe" in DB
    -- current - pT -> pTObj
    -- pTObj - rdfs:label -> "universe"
    mw.log(objTest:addPropertyWithIri(pT, pTObjTemp1))
    mw.log(ObjTemp1:addPropertyWithLitteral(rdfs.."label","universe"))
    result = objTest:checkItem(pT,valueInWiki)
    html = html .."34 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'

    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_value_equal"><span class="plainlinks">[https://www.wikidata.org/wiki/Special:GoToLinkedPage/enwiki/Q1 universe]</span><span class="plainlinks"><small>([http://www.wikidata.org/entity/Q1 Q1])</small></span></div>') ..'\n'


    valueInWiki =  "Q1;Q2"
    --"universe" in DB
    result = objTest:checkItem(pT,valueInWiki)
    html = html .."35 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'

    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_new_value linkedwiki_tooltip" data-toggle="tooltip" data-placement="bottom" title="Currently in DB : universe"><span class="plainlinks">[https://www.wikidata.org/wiki/Special:GoToLinkedPage/enwiki/Q2 Earth]</span><span class="plainlinks"><small>([http://www.wikidata.org/entity/Q2 Q2])</small></span>, <span class="plainlinks">[https://www.wikidata.org/wiki/Special:GoToLinkedPage/enwiki/Q1 universe]</span><span class="plainlinks"><small>([http://www.wikidata.org/entity/Q1 Q1])</small></span></div>') ..'\n'

    valueInWiki = " Q1 ; Q2 "
    --"Title1;Title2" in DB
    --"Title1" in DB
    -- current - pT -> pTObj2
    -- pTObj2 - rdfs:label -> "Title2"
    mw.log(objTest:addPropertyWithIri(pT,pTObjTemp2))
    mw.log(ObjTemp2:addPropertyWithLitteral(rdfs.."label","Earth"))
    result = objTest:checkItem(pT,valueInWiki)
    html = html .."36 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_value_equal"><span class="plainlinks">[https://www.wikidata.org/wiki/Special:GoToLinkedPage/enwiki/Q2 Earth]</span><span class="plainlinks"><small>([http://www.wikidata.org/entity/Q2 Q2])</small></span>, <span class="plainlinks">[https://www.wikidata.org/wiki/Special:GoToLinkedPage/enwiki/Q1 universe]</span><span class="plainlinks"><small>([http://www.wikidata.org/entity/Q1 Q1])</small></span></div>') ..'\n'

    valueInWiki = ""
    --"Title1;Title2" in DB
    result = objTest:checkItem(pT,valueInWiki)
    html = html .."37 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_value_equal"><span class="plainlinks">[https://www.wikidata.org/wiki/Special:GoToLinkedPage/enwiki/Q2 Earth]</span><span class="plainlinks"><small>([http://www.wikidata.org/entity/Q2 Q2])</small></span>, <span class="plainlinks">[https://www.wikidata.org/wiki/Special:GoToLinkedPage/enwiki/Q1 universe]</span><span class="plainlinks"><small>([http://www.wikidata.org/entity/Q1 Q1])</small></span></div>') ..'\n'


    valueInWiki = "Title3"
    --"Title1;Title2" in DB
    result = objTest:checkItem(pT,valueInWiki)
    html = html .."38 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_new_value linkedwiki_tooltip" data-toggle="tooltip" data-placement="bottom" title="Currently in DB : Earth, universe">Title3, <span class="plainlinks">[https://www.wikidata.org/wiki/Special:GoToLinkedPage/enwiki/Q2 Earth]</span><span class="plainlinks"><small>([http://www.wikidata.org/entity/Q2 Q2])</small></span>, <span class="plainlinks">[https://www.wikidata.org/wiki/Special:GoToLinkedPage/enwiki/Q1 universe]</span><span class="plainlinks"><small>([http://www.wikidata.org/entity/Q1 Q1])</small></span></div>') ..'\n'

    valueInWiki = "Title3;Title4"
    --"Title1;Title2" in DB
    result = objTest:checkItem(pT,valueInWiki)
    html = html .."39 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_new_value linkedwiki_tooltip" data-toggle="tooltip" data-placement="bottom" title="Currently in DB : Earth, universe">Title3, Title4, <span class="plainlinks">[https://www.wikidata.org/wiki/Special:GoToLinkedPage/enwiki/Q2 Earth]</span><span class="plainlinks"><small>([http://www.wikidata.org/entity/Q2 Q2])</small></span>, <span class="plainlinks">[https://www.wikidata.org/wiki/Special:GoToLinkedPage/enwiki/Q1 universe]</span><span class="plainlinks"><small>([http://www.wikidata.org/entity/Q1 Q1])</small></span></div>') ..'\n'

    html = html .."----------------------------------------------------------------------------" ..'\n'
    html = html .."TEST : printDateInWiki" ..'\n'
    --Linkedwiki:printDateInWiki(format, valueInWiki, valueInDB)

    local dateFormat = "d M Y"
    --  {{#time:d M Y|2004-12-06}}
    --"2004-12-06"^^xsd..'date'
    result = objTest:printDateInWiki( "2004-12-06", "2004-12-06", dateFormat)
    html = html .."40 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_value_equal">06 Dec 2004</div>') ..'\n'

    result = objTest:printDateInWiki("2004-12-07", "2004-12-06",dateFormat)
    html = html .."41 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_new_value linkedwiki_tooltip" data-toggle="tooltip" data-placement="bottom" title="Currently in DB : 06 Dec 2004">07 Dec 2004</div>') ..'\n'

    html = html .."----------------------------------------------------------------------------" ..'\n'
    html = html .."TEST : checkDate" ..'\n'

    dateFormat = "d M Y"
    local pTDate = 'http://example.com/date'
    local pTDateTime = 'http://example.com/dateTime'

    valueInWiki = ""
    --0 in DB
    result = objTest:checkDate(pTDate,valueInWiki, dateFormat)
    html = html .."42 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'') ..'\n'

    valueInWiki = "2004-12-06"
    --0 in DB
    result = objTest:checkDate(pTDate,valueInWiki, dateFormat)
    html = html .."BEGIN : "..'\n' ..valueInWiki ..'\n'.."END" ..'\n'
    html = html .."43 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div>06 Dec 2004</div>') ..'\n'

    valueInWiki =  ""
    --"2004-12-06" in DB
    -- current - pT ->  "2004-12-06"
    mw.log(objTest:addProperty(pTDate,"2004-12-06",xsd..'date'))
    result = objTest:checkDate(pTDate,valueInWiki, dateFormat)
    html = html .."44 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div>06 Dec 2004</div>') ..'\n'

    valueInWiki =  "2004-12-06"
    --"2004-12-06" in DB
    result = objTest:checkDate(pTDate,valueInWiki, dateFormat)
    html = html .."45 RESULT BEGIN11 : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_value_equal">06 Dec 2004</div>') ..'\n'

    --"2004-12-06" in DB
    -- current - pT ->  "2004-12-06"
    mw.log(objTest:addProperty(pTDateTime,"2004-12-06T00:00:00Z",xsd..'dateTime'))
    result = objTest:checkDate(pTDateTime,valueInWiki, dateFormat)
    html = html .."46 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_value_equal">06 Dec 2004</div>') ..'\n'

    valueInWiki =  ""
    --"2004-12-06T00:00:00Z" in DB
    result = objTest:checkDate(pTDateTime,valueInWiki, dateFormat)
    html = html .."47 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div>06 Dec 2004</div>') ..'\n'

    valueInWiki =  "2004-12-07"
    --"2004-12-06" in DB
    result = objTest:checkDate(pTDate,valueInWiki, dateFormat)
    html = html .."48 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'

    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_new_value linkedwiki_tooltip" data-toggle="tooltip" data-placement="bottom" title="Currently in DB : 06 Dec 2004">07 Dec 2004</div>') ..'\n'

    local idConfigWikidata ='http://www.wikidata.org'
    local taglang ='fr'
    iriWikidata = wd .. "Q132845"
    objWikidata = linkedwiki.new(iriWikidata,idConfigWikidata,taglang)
    result = objWikidata:checkDate(wdt.."P569","1100-1-1",dateFormat)
    html = html .."49 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div class="linkedwiki_new_value linkedwiki_tooltip" data-toggle="tooltip" data-placement="bottom" title="Currently in DB : not a date">01 Jan 1100</div>') ..'\n'

    objWikidata = linkedwiki.new(iriWikidata,idConfigWikidata,taglang)
    result = objWikidata:checkDate(wdt.."P569","",dateFormat)
    html = html .."50 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div><strong class="error">Error: Invalid time.</strong></div>') ..'\n'

    objWikidata = linkedwiki.new(iriWikidata,idConfigWikidata,taglang)
    result = objWikidata:checkDate(wdt.."P569","Truc",dateFormat)
    html = html .."51 RESULT BEGIN : "..'\n' ..result ..'\n'.."END" ..'\n'
    html = html .."RESULT : " .. p.checkString(result,'<div>Error is not a date (0000-00-00) : Truc</div>') ..'\n'

--    mw.log(linkedwiki.getLastQuery())
    mw.log(objTest:removeSubject())


    --return html
    return "<nowiki><pre>"..mw.text.encode( html).."</pre></nowiki>"

end

return p
