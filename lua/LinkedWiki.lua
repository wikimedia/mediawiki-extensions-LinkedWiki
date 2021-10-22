--[[
	Registers and defines functions to access LinkedWiki through the Scribunto extension
	Provides Lua setupInterface
 @copyright (c) 2021 Bordercloud.com
 @author Karima Rafes <karima.rafes@bordercloud.com>
 @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 @license CC-BY-SA-4.0
]]

-- module
local linkedwiki = {}
local php


function linkedwiki.checkResult(result, errorMessage)
  if errorMessage ~= nil then
 		error(tostring(errorMessage),2)
 	end
   return result
end
-- FIX temporary the problem https://phabricator.wikimedia.org/T264413 for Mediawiki 1.35
local currentFrame
function linkedwiki.setCurrentFrame(frame)
	currentFrame = frame
end
function linkedwiki.getCurrentFrame()
	if currentFrame ~= nil then
         return currentFrame
    end
    if mw.getCurrentFrame ~= nil then
    	return mw.getCurrentFrame()
    else
    	error( 'ERROR T264413. ' .. mw.message.new( "linkedwiki-lua-error-currentframe-nil", valueInDB):plain())
    end
end

function linkedwiki.isEmpty(s)
    return s == nil or s == ''
end

function linkedwiki.loadStyles()
    php.loadStyles()
end

function linkedwiki.print_r ( t )
    local print_r_cache={}
    local function sub_print_r(t,indent)
            if (type(t)=="table") then
                for pos,val in pairs(t) do
                    if (type(val)=="table") then
                        mw.log(indent.."["..pos.."] => "..tostring(t).." {")
                        sub_print_r(val,indent..string.rep(" ",string.len(pos)+8))
                        mw.log(indent..string.rep(" ",string.len(pos)+6).."}")
                    else
                        mw.log(indent.."["..pos.."] => "..tostring(val))
                    end
                end
            else
                mw.log(indent..tostring(t))
            end
    end
    sub_print_r(t,"  ")
end

-- Obsolete ?
-- function linkedwiki.info()
--     local wikitext = mw.html.create('div')
--     wikitext:addClass("plainlinks")
--     wikitext:wikitext(php.info())
--     return tostring(wikitext)
-- end

function linkedwiki.timeStamp(dateStringArg)
    local patternDateTime = '^(%d%d%d%d)-(%d?%d)-(%d?%d)T(%d?%d):(%d?%d):(%d?%d)(.-)$';
    local patternDate = '^(%d%d%d%d)-(%d?%d)-(%d?%d)$';
    local returnTime = 0
    if string.find(dateStringArg, patternDateTime) then
        local inYear, inMonth, inDay, inHour, inMinute, inSecond, inZone =
        string.match(dateStringArg,patternDateTime)
        local zHours, zMinutes = string.match(inZone, '^(.-):(%d%d)$')

        returnTime = os.time({year=inYear, month=inMonth, day=inDay, hour=inHour, min=inMinute, sec=inSecond, isdst=false})

        if zHours then
            returnTime = returnTime - ((tonumber(zHours)*3600) + (tonumber(zMinutes)*60))
        end
    elseif string.find(dateStringArg, patternDate) then
        local inDateYear, inDateMonth, inDateDay =
        string.match(dateStringArg, patternDate)
        returnTime = os.time({year=inDateYear, month=inDateMonth, day=inDateDay, hour=0, min=0, sec=0, isdst=false})
    else
        return nil
    end
    return returnTime
end

--[[
    setConfig can replace setEndpoint and setGraph.
]]
function linkedwiki.setConfig(iriDataset)
	return linkedwiki.checkResult(php.setConfig(iriDataset))
end
function linkedwiki.getConfig()
    return linkedwiki.checkResult(php.getConfig())
end
function linkedwiki.getDefaultConfig()
    return linkedwiki.checkResult(php.getDefaultConfig())
end

function linkedwiki.setEndpoint(urlEndpoint)
    return linkedwiki.checkResult(php.setEndpoint(urlEndpoint))
end

function linkedwiki.setDebug(boolDebug)
    return linkedwiki.checkResult(php.setDebug(boolDebug))
end
function linkedwiki.isDebug()
    return linkedwiki.checkResult(php.isDebug())
end

--function linkedwiki.setGraph(iriGraph)
--    return php.setGraph(iriGraph)
--end

function linkedwiki.setSubject(iriSubject)
    return linkedwiki.checkResult(php.setSubject(iriSubject))
end

function linkedwiki.setLang(tagLang)
    return linkedwiki.checkResult(php.setLang(tagLang))
end

function linkedwiki.getLang(tagLang)
    return linkedwiki.checkResult(php.getLang())
end

function linkedwiki.getLastQuery()
    return linkedwiki.checkResult(php.getLastQuery())
end

function linkedwiki.getValue(iriProperty, iriSubject)
    return linkedwiki.checkResult(php.getValue(iriProperty, iriSubject))
end

function linkedwiki.query(q)
    return linkedwiki.checkResult(php.query(q))
end

function linkedwiki.getString(iriProperty, tagLang, iriSubject)
    --checkTypeMulti( 'getString', 1, tagLang, { 'string', 'nil' } )
    return linkedwiki.checkResult(php.getString(iriProperty, tagLang, iriSubject))
end

function linkedwiki.addPropertyWithIri(iriProperty, iriValue, iriSubject)
    return linkedwiki.checkResult(php.addPropertyWithIri(iriProperty, iriValue, iriSubject))
end

function linkedwiki.addPropertyWithLiteral(iriProperty, value, type, tagLang, iriSubject)
    return linkedwiki.checkResult(php.addPropertyWithLiteral(iriProperty, value, type, tagLang, iriSubject))
end

function linkedwiki.removeSubject(iriSubject)
    return linkedwiki.checkResult(php.removeSubject(iriSubject))
end

function linkedwiki.loadData(titles)
    return linkedwiki.checkResult(php.loadData(titles))
end

local currentFullPageName
function linkedwiki.getCurrentTitle()
    local currentFullPageName
    if currentFullPageName == nil then
         currentFullPageName = linkedwiki.getCurrentFrame():preprocess('{{FULLPAGENAME}}')
    end
    return mw.title.new(currentFullPageName)
end

function linkedwiki.getCurrentIRI()
    return mw.uri.decode(linkedwiki.getCurrentTitle():fullUrl())
end

function linkedwiki.getProtocol()
    return linkedwiki.checkResult(php.getProtocol())
end


function linkedwiki.explode(div, str)
    if (div == '') then return false end
    if (str == '') then return {} end
    local pos, arr = 0, {}
    -- for each divider found
    for st, sp in function() return string.find(str, div, pos, true) end do
        table.insert(arr, string.sub(str, pos, st - 1)) -- Attach chars left of current divider
        pos = sp + 1 -- Jump past current divider
    end
    table.insert(arr, string.sub(str, pos)) -- Attach chars right of last divider
    return arr
end

function linkedwiki.concatWithComma(tab,tabOrder)
    local html = ""
    local comma = ""
    if tabOrder then
		for id, iri in ipairs(tabOrder) do
			if tab[iri] then
				html = html .. comma .. tab[iri]
				comma=", "
			end
		end
    else
        for key, value in pairs(tab) do
            html = html .. comma .. value
            comma=", "
        end
    end
    return html
end

function linkedwiki.buildDivSimple(isDifferent,html,valueInDB)
    local result = ""
    local div = mw.html.create('div')

    if isDifferent then
        div:addClass("mw-ext-linkedwiki-new-value")
        div:addClass("mw-ext-linkedwiki-tooltip")
        div:attr("data-toggle", "tooltip")
        div:attr("data-placement", "bottom")
        if string.find(tostring(valueInDB), '^t%d+$') then
            div:attr("title", mw.message.new( "linkedwiki-lua-tooltip-db-currently-unknown-value" ):plain())
        else
            div:attr("title", mw.message.new( "linkedwiki-lua-tooltip-db-currently-value", valueInDB):plain())
        end
    else
        div:addClass("mw-ext-linkedwiki-value-equal")
    end

    if not linkedwiki.isEmpty(html) then
        div:wikitext(html)
        result = tostring(div)
    end

    return result
end

function linkedwiki.buildDiv(countInWiki,tabHtmlInWiki,countInDB,tabHtmlInDB,tabTitleInDB,tabOrder)
    local result = ""
    local html = ""
    local html2 = ""
    local div = mw.html.create('div')

    if countInWiki > 0 then
        html = html .. linkedwiki.concatWithComma(tabHtmlInWiki,tabOrder)
        div:addClass("mw-ext-linkedwiki-new-value")
        if countInDB > 0  then
            html = html .. ", " .. linkedwiki.concatWithComma(tabHtmlInDB,tabOrder)
            div:addClass("mw-ext-linkedwiki-tooltip")
            div:attr("data-toggle", "tooltip")
            div:attr("data-placement", "bottom")
            html2 = linkedwiki.concatWithComma(tabTitleInDB,tabOrder)
            div:attr("title", mw.message.new( "linkedwiki-lua-tooltip-db-currently-value", html2):plain())
        end
    elseif countInDB > 0 then
        div:addClass("mw-ext-linkedwiki-value-equal")
        html = html .. linkedwiki.concatWithComma(tabHtmlInDB,tabOrder)
    end

    if not linkedwiki.isEmpty(html) then
        div:wikitext(html)
        result = tostring(div)
    end

    return result
end

-- class


-- START CLASS Linkedwiki

--Linkedwiki.php = mw_interface

function linkedwiki.new(subject,config,tagLang,debug)
    local Linkedwiki = {}
    Linkedwiki.config = config
    Linkedwiki.tagLang = tagLang
    Linkedwiki.subject = subject
    Linkedwiki.databaseIsUpdate = true
    Linkedwiki.debug = debug

    --    function Linkedwiki:info()
    --        local wikitext = mw.html.create('div')
    --        wikitext:addClass("plainlinks")
    --        wikitext:wikitext(php.info())
    --        return tostring(wikitext)
    --    end

    --[[
        setConfig can replace setEndpoint and setGraph.
    ]]
    function Linkedwiki:setConfig(iriDataset)
        local result =""
        if linkedwiki.isEmpty(iriDataset) then
            self.config =  linkedwiki.getDefaultConfig()
        else
            self.config = iriDataset
        end
    end
    function Linkedwiki:getConfig()
        return self.config or  linkedwiki.getDefaultConfig()
    end

    function Linkedwiki:setLang(tagLang)
       -- return self.linkedwiki.setLang(tagLang)
       if linkedwiki.isEmpty(tagLang) then
           self.tagLang = linkedwiki.getLang()
       else
           self.tagLang = tagLang
       end
    end

    function Linkedwiki:getLang()
        return self.tagLang or linkedwiki.getLang()
    end

    function Linkedwiki:setSubject(iriSubject)
        self.subject = iriSubject
    end

    function Linkedwiki:getSubject()
        return self.subject
    end

    function Linkedwiki:getMaintenanceCategory()
        local result = ''
        local labelInDB = self:getValue("http://www.w3.org/2000/01/rdf-schema#label", linkedwiki.getCurrentIRI())
        -- remove? and not linkedwiki.isEmpty(labelInDB)
        if self.databaseIsUpdate then
            result = ""
        else
            result = "[[Category:Check data]]"
        end
        return result
    end

    function Linkedwiki:setDebug(boolDebug)
        self.debug = boolDebug
    end
    function Linkedwiki:isDebug()
        return self.debug or linkedwiki.isDebug()
    end

    function Linkedwiki:getLastQuery()
        return linkedwiki.getLastQuery()
    end
    function Linkedwiki:initConfig()
        linkedwiki.setSubject(self:getSubject())
        linkedwiki.setConfig(self:getConfig())
        linkedwiki.setLang(self:getLang())
        linkedwiki.setDebug(self:isDebug())
    end

    function Linkedwiki:getValue(iriProperty)
        self:initConfig()
        return linkedwiki.getValue(iriProperty)
    end

    function Linkedwiki:getString(iriProperty, tagLang)
        self:initConfig()
        return linkedwiki.getString(iriProperty, tagLang)
    end

    function Linkedwiki:addPropertyWithIri(iriProperty, iriValue)
        self:initConfig()
        return linkedwiki.addPropertyWithIri(iriProperty, iriValue)
    end

    function Linkedwiki:addPropertyWithLiteral(iriProperty, value, type, tagLang)
        self:initConfig()
        return  linkedwiki.addPropertyWithLiteral(iriProperty, value, type, tagLang)
    end
    function Linkedwiki:addProperty(iriProperty, value, type)
        return  self:addPropertyWithLiteral(iriProperty, value,type,'')
    end

    function Linkedwiki:addPropertyString(iriProperty, value, tagLang)
        return  self:addPropertyWithLiteral(iriProperty, value, nil, tagLang)
    end

    function Linkedwiki:removeSubject()
        self:initConfig()
        return linkedwiki.removeSubject()
    end

    function Linkedwiki:loadData(titles)
        self:initConfig()
        return linkedwiki.loadData(titles)
    end

    function Linkedwiki:printItemInWiki(valueInWiki, valueInDB, tagLang)
        --mw.log("linkedwiki.printTitleInWiki(valueInWiki "..valueInWiki..",valueInDB ".. valueInDB..")")
        local listIri = nil
        local listValue = nil
        local text = ""
        local titleInDB = ""

        local tabIriInDB = {}
        local tabTitleInDB = {}
        local tabHtmlInDB = {}

        local tabHtmlInWiki = {}
        local cleanId =""
        local titleInWiki =""
        local idInWiki=""

        local tabOrder = {}

        local isDifferent = false
        local html = ''
        local comma = ''
        local listValueInDB = ''

        if not linkedwiki.isEmpty(valueInDB) then
            listIri = linkedwiki.explode(";", valueInDB)
            for i, iri in ipairs(listIri) do
                self:initConfig()
                titleInDB = linkedwiki.getString("http://www.w3.org/2000/01/rdf-schema#label", tagLang, iri)

                --text = '<span class="plainlinks">[' .. iri .. ' ' .. titleInDB .. ']</span>'
                cleanId = string.match(iri, "(Q.*)")
                text = ""
                text = '<span class="plainlinks">'
                            .. '[https://www.wikidata.org/wiki/Special:GoToLinkedPage/'.. self:getLang(tagLang)
                            ..'wiki/' ..cleanId
                            ..' '
                            ..  titleInDB
                            .. ']</span>'
                text = text.. '<span class="plainlinks"><small>([' .. iri .. ' '..cleanId..'])</small></span>'

                tabIriInDB[iri]= true

                tabTitleInDB[iri]= titleInDB
                tabHtmlInDB[iri]= text
                table.insert(tabOrder, iri)
            end
        end

        if not linkedwiki.isEmpty(valueInWiki) then
            listValue = linkedwiki.explode(";", valueInWiki)
            local wikidata
            local iriInWikidata

            for i, id in ipairs(listValue) do
                idInWiki = mw.text.trim( id )
                cleanId = string.match(idInWiki, "(Q.*)")
                text = ""
                --mw.log(idInWiki)
                if not linkedwiki.isEmpty(cleanId) then
                    iriInWikidata = "http://www.wikidata.org/entity/" .. cleanId
                    wikidata = linkedwiki.new(iriInWikidata,"http://www.wikidata.org",self:getLang(tagLang))
                    titleInWiki = wikidata:getString("http://www.w3.org/2000/01/rdf-schema#label", self:getLang(tagLang))
                    text = '<span class="plainlinks">'
                            .. '[https://www.wikidata.org/wiki/Special:GoToLinkedPage/'.. self:getLang(tagLang)
                            ..'wiki/' ..cleanId
                            ..' '
                            ..  titleInWiki
                            .. ']</span>'

                    text = text.. '<span class="plainlinks"><small>([' .. iriInWikidata .. ' '..cleanId..'])</small></span>'

                    --mw.log(text..'EE')
                else -- it is not a ID
                    tabTitleInWiki = idInWiki
                    iriInWikidata = idInWiki
                    text = idInWiki
                end

                tabHtmlInWiki[iriInWikidata]= text

                if not tabIriInDB[iriInWikidata] then
                     tabIriInDB[iriInWikidata]= false
                     isDifferent = true
                     table.insert(tabOrder, iriInWikidata)
                elseif tabTitleInDB[iriInWikidata] ~= titleInWiki then
                    tabHtmlInDB[iriInWikidata]= text
                   isDifferent = true
                --   tabTitleInDB[iriInWikidata] = titleInWiki
                end
            end
        end

        if isDifferent then
            self.databaseIsUpdate = false
        end

        for id, iri in ipairs(tabOrder) do

            if tabIriInDB[iri] then
               html = html .. comma .. tabHtmlInDB[iri]
               listValueInDB  = listValueInDB .. comma .. tabTitleInDB[iri]
            else
               html = html .. comma .. tabHtmlInWiki[iri]
            end
            comma = ', '
        end
        return linkedwiki.buildDivSimple(isDifferent,html,listValueInDB)
    end

    function Linkedwiki:printTitleInWiki(valueInWiki, valueInDB, tagLang)
        --mw.log("linkedwiki.printTitleInWiki("..valueInWiki..",".. valueInDB..")")

        local listIri = nil
        local listValue = nil
        local text = ""
        local titleInDB = ""
        local iriInWiki = ""

        local tabIriInDB = {}
        local tabTitleInDB = {}
        local tabHtmlInDB = {}
        local countInDB = 0
        local userEmailInDB ={}

        local tabHtmlInWiki = {}
        local countInWiki = 0
        local cleanTitle =""

        local tabOrder = {}

        if not linkedwiki.isEmpty(valueInDB) then
            listIri = linkedwiki.explode(";", valueInDB)
            for i, iri in ipairs(listIri) do
                self:initConfig()
                titleInDB = linkedwiki.getString("http://www.w3.org/2000/01/rdf-schema#label", tagLang, iri)
                text = '<span class="plainlinks">[' .. iri .. ' ' .. titleInDB .. ']</span>'
                tabIriInDB[iri]= true
                tabTitleInDB[iri]= titleInDB
                tabHtmlInDB[iri]= text
                countInDB = countInDB + 1
                table.insert(tabOrder, iri)
            end
        end

--        mw.log(linkedwiki.getLastQuery())
--        for key, value in pairs(tabTitleInDB) do
--          mw.log( key.." "..value)
--        end

        if not linkedwiki.isEmpty(valueInWiki) then
            listValue = linkedwiki.explode(";", valueInWiki)
            for i, title in ipairs(listValue) do
                cleanTitle = mw.text.trim( title )
                text = '[[' .. cleanTitle .. ']]'
                iriInWiki = mw.title.new(cleanTitle):fullUrl()

                if not tabIriInDB[iriInWiki] then
                    tabHtmlInWiki[iriInWiki]= text
                    countInWiki = countInWiki +1
                    table.insert(tabOrder, iriInWiki)
                elseif tabTitleInDB[iriInWiki] ~= cleanTitle then
                    tabHtmlInWiki[iriInWiki]= text
                    countInWiki = countInWiki +1
                end
            end
        end

        if countInWiki > 0 then
            self.databaseIsUpdate = false
        end

--        for key, value in pairs(tabOrder) do
--          mw.log( key.." "..value)
--        end

        return linkedwiki.buildDiv(countInWiki,tabHtmlInWiki,countInDB,tabHtmlInDB,tabTitleInDB,tabOrder)
    end

    function Linkedwiki:printValueInWiki(valueInWiki, valueInDB)
        local div = mw.html.create('div')

        if not linkedwiki.isEmpty(valueInWiki) then
            div:wikitext(valueInWiki)

            if valueInWiki == valueInDB then
                div:addClass("mw-ext-linkedwiki-value-equal")
            else
                self.databaseIsUpdate = false
                div:addClass("mw-ext-linkedwiki-new-value")
                if not linkedwiki.isEmpty(valueInDB) then
                    -- Wikidata blank node
                    div:addClass("mw-ext-linkedwiki-tooltip")
                    div:attr("data-toggle", "tooltip")
                    div:attr("data-placement", "bottom")
                    if string.find(tostring(valueInDB), '^t%d+$') then
                        div:attr("title", mw.message.new( "linkedwiki-lua-tooltip-db-currently-unknown-value" ):plain())
                    else
                        div:attr("title", mw.message.new( "linkedwiki-lua-tooltip-db-currently-value", valueInDB):plain())
                    end
                end
            end
        elseif not linkedwiki.isEmpty(valueInDB) then
            div:wikitext(valueInDB)
        end
        return tostring(div)
    end

    function Linkedwiki:printUserInWiki(valueInWiki, valueInDB, tagLang)
        local listIri = nil
        local listValue = nil
        local text = ""
        local titleInDB = ""
        local iriInWiki = ""

        local tabIriInDB = {}
        local tabTitleInDB = {}
        local tabHtmlInDB = {}
        local countInDB = 0
        local userEmailInDB ={}

        local tabHtmlInWiki = {}
        local countInWiki = 0
        local cleanTitle =""

        local tabOrder = {}

        if not linkedwiki.isEmpty(valueInDB) then
            listIri = linkedwiki.explode(";", valueInDB)
            for i, iri in ipairs(listIri) do
                self:initConfig()
                titleInDB = linkedwiki.getString("http://www.w3.org/2000/01/rdf-schema#label", tagLang, iri)
                text = '<span class="plainlinks">[' .. iri .. ' ' .. titleInDB .. ']</span>'

                userEmailInDB = linkedwiki.explode(";",linkedwiki.getValue("http://www.w3.org/2006/vcard/ns#email", iri))
               -- text = text .. "COUCOU"..linkedwiki.getLastQuery()
                for i, email in ipairs(userEmailInDB) do
                    text = text .. '<sub><span class="plainlinks" style="font-size: large;">[' .. email .. ' &#9993;]</span></sub>'
                end

               -- text = text .. "COUCOU1"

                tabIriInDB[iri]= true
                tabTitleInDB[iri]= titleInDB
                tabHtmlInDB[iri]= text
                countInDB = countInDB + 1
                table.insert(tabOrder, iri)
            end
        end

        if not linkedwiki.isEmpty(valueInWiki) then
            listValue = linkedwiki.explode(";", valueInWiki)
            for i, title in ipairs(listValue) do
                cleanTitle = mw.text.trim( title )
                text = '[[User:' .. cleanTitle .. '|' .. cleanTitle .. ']]'
                iriInWiki = mw.title.new("User:"..cleanTitle):fullUrl()

                if not tabIriInDB[iriInWiki] then
                    tabHtmlInWiki[iriInWiki]= text
                    countInWiki = countInWiki +1
                    table.insert(tabOrder, iriInWiki)
                elseif tabTitleInDB[iriInWiki] ~= cleanTitle then
                    tabHtmlInWiki[iriInWiki]= text
                    countInWiki = countInWiki +1
                end
            end
        end

        if countInWiki > 0 then
            self.databaseIsUpdate = false
        end

        return linkedwiki.buildDiv(countInWiki,tabHtmlInWiki,countInDB,tabHtmlInDB,tabTitleInDB,tabOrder)
    end

    function Linkedwiki:printImageInWiki(valueInWiki, valueInDB)
       local linkImage = ''
       local srcImage = ''
       local divClass = ''
       local tooltipTitle = nil

       if not linkedwiki.isEmpty(valueInWiki) then
            linkImage = valueInWiki
            srcImage = string.gsub( valueInWiki, "https?://", linkedwiki.getProtocol().."//" )

            if valueInWiki == valueInDB then
                divClass = "mw-ext-linkedwiki-value-equal"
            else
                self.databaseIsUpdate = false
                divClass = "mw-ext-linkedwiki-new-value"
                if not linkedwiki.isEmpty(valueInDB) then
                    if string.find(tostring(valueInDB), '^http.*$') then
                        tooltipTitle = mw.message.new( "linkedwiki-lua-tooltip-db-currently-value", valueInDB):plain()
                    else
                        tooltipTitle = mw.message.new( "linkedwiki-lua-tooltip-db-currently-unknown-value" ):plain()
                    end
                end
            end
       elseif not linkedwiki.isEmpty(valueInDB) then
            linkImage = valueInDB
            srcImage = string.gsub( valueInDB, "https?://", linkedwiki.getProtocol().."//" )
       else -- Empty
            return ''
       end

        return self:renderImageInWikiText(linkImage, linkImage, tooltipTitle, divClass)
    end

    function Linkedwiki:renderImageInWikiText(linkImage, srcImage, tooltipTitle, divClasses)
        local cssClasses = ''
        local otherAttr = ''
        if not linkedwiki.isEmpty(divClasses) then
            cssClasses = divClasses
        end
        if not linkedwiki.isEmpty(tooltipTitle) then
            cssClasses = cssClasses .. " mw-ext-linkedwiki-tooltip"
            otherAttr = 'title="'..tooltipTitle..'" data-toggle="tooltip" data-placement="bottom"'
        end
        return '<div class="'..cssClasses..'" '..otherAttr..'><span class="plainlinks">['..linkImage..' '..srcImage..']</span></div>'
    end

    function Linkedwiki:printDateInWiki(valueInWiki, valueInDB, format)
        local div = mw.html.create('div')

        if not linkedwiki.isEmpty(valueInWiki) then
            local timeStampInWiki = linkedwiki.timeStamp(valueInWiki)
            if linkedwiki.isEmpty(timeStampInWiki) then
                div:wikitext(
                    mw.message.new( "linkedwiki-lua-date-error", valueInWiki):plain()
                )
            else
                div:wikitext(linkedwiki.getCurrentFrame():preprocess(
                '{{#iferror: {{#time:' .. format .. '|' .. valueInWiki .. '}} | '..
                mw.message.new( "linkedwiki-lua-time-parser-error", valueInWiki):plain()
                ..' }}'
                ))
                if not linkedwiki.isEmpty(valueInDB) then
                    local timeStampInDB = linkedwiki.timeStamp(valueInDB)
                    if linkedwiki.timeStamp(valueInWiki) == timeStampInDB then
                        div:addClass("mw-ext-linkedwiki-value-equal")
                    else
                        self.databaseIsUpdate = false
                        div:addClass("mw-ext-linkedwiki-new-value")
                        if not linkedwiki.isEmpty(valueInDB) then
                            div:addClass("mw-ext-linkedwiki-tooltip")
                            div:attr("data-toggle", "tooltip")
                            div:attr("data-placement", "bottom")
                            if string.find(tostring(valueInDB), '^t%d+$') then
                                div:attr(
                                    "title",
                                    mw.message.new( "linkedwiki-lua-tooltip-db-currently-date-unknown" ):plain()
                                 )
                            elseif linkedwiki.isEmpty(timeStampInDB) then
                                div:attr(
                                    "title",
                                    mw.message.new( "linkedwiki-lua-tooltip-db-currently-date-wrong" ):plain()
                                 )
                            else
                                div:attr(
                                   "title",
                                    mw.message.new(
                                        "linkedwiki-lua-tooltip-db-currently-value",
                                        linkedwiki.getCurrentFrame():preprocess(
                '{{#iferror: {{#time:' .. format .. '|' .. valueInDB .. '}} | '..
                  mw.message.new( "linkedwiki-lua-time-parser-error",valueInDB):plain()
                ..' }}'
                                        )
                                    ):plain()
                                )
                            end
                        end
                    end
                end
            end
        elseif not linkedwiki.isEmpty(valueInDB) then
            if string.find(tostring(valueInDB), '^t%d+$') then
                div:wikitext(mw.message.new( "linkedwiki-lua-date-error", valueInDB):plain())
            else
                div:wikitext(linkedwiki.getCurrentFrame():preprocess(
                '{{#iferror: {{#time:' .. format .. '|' .. valueInDB .. '}} | '..
                  mw.message.new( "linkedwiki-lua-time-parser-error", valueInDB):plain()
                ..' }}'
                ))
            end
        else -- Empty
            return ''
        end
        return tostring(div)
    end

    function Linkedwiki:printExternLinkInWiki(valueInWiki, valueInDB, label)
        local div = mw.html.create('div')

        if not linkedwiki.isEmpty(valueInWiki) then
            div:wikitext('[' .. valueInWiki .. ' ' .. label .. ']')

            if valueInWiki == valueInDB then
                div:addClass("mw-ext-linkedwiki-value-equal")
            else
                self.databaseIsUpdate = false
                div:addClass("mw-ext-linkedwiki-new-value")
                if not linkedwiki.isEmpty(valueInDB) then
                    div:addClass("mw-ext-linkedwiki-tooltip")
                    div:attr("data-toggle", "tooltip")
                    div:attr("data-placement", "bottom")
                    if string.find(tostring(valueInDB), '^t%d+$') then
                        div:attr("title", "Currently in DB: unknown value")
                    else
                        div:attr("title", "Currently in DB: " .. valueInDB)
                    end
                end
            end
        elseif not linkedwiki.isEmpty(valueInDB) then
            div:wikitext('[' .. valueInDB .. ' ' .. label .. ']')
        else -- Empty
            return ''
        end
        return tostring(div)
    end

    function Linkedwiki:printLinkInWiki(valueInWiki, valueInDB, link)
        local div = mw.html.create('div')
        if not linkedwiki.isEmpty(valueInWiki) then
            div:wikitext('<span class="plainlinks">[' .. link .. ' ' .. valueInWiki .. ']</span>')
            if valueInWiki == valueInDB then
                div:addClass("mw-ext-linkedwiki-value-equal")
            else
                self.databaseIsUpdate = false
                div:addClass("mw-ext-linkedwiki-new-value")
                if not linkedwiki.isEmpty(valueInDB) then
                    div:addClass("mw-ext-linkedwiki-tooltip")
                    div:attr("data-toggle", "tooltip")
                    div:attr("data-placement", "bottom")
                    if string.find(tostring(valueInDB), '^t%d+$') then
                        div:attr("title", "Currently in DB: unknown value")
                    else
                        div:attr("title", "Currently in DB: " .. valueInDB)
                    end
                end
            end
        elseif not linkedwiki.isEmpty(valueInDB) then
            div:wikitext('<span class="plainlinks">[' .. link .. ' ' .. valueInDB .. ']</span>')
        else -- Empty
            return ''
        end
        return tostring(div)
    end

    function Linkedwiki:checkValue(property, valueInWiki)
        linkedwiki.loadStyles()
        return self:printValueInWiki(valueInWiki, self:getValue(property))
    end

    function Linkedwiki:checkString(property, valueInWiki, tagLang)
        linkedwiki.loadStyles()
        return self:printValueInWiki(valueInWiki, self:getString(property, tagLang))
    end

    function Linkedwiki:checkItem(property, valueInWiki, tagLang)
        linkedwiki.loadStyles()
        return self:printItemInWiki(valueInWiki, self:getValue(property), tagLang)
    end

    function Linkedwiki:checkImage(property, valueInWiki)
        linkedwiki.loadStyles()
        return self:printImageInWiki(valueInWiki, self:getValue(property))
    end

    function Linkedwiki:checkDate(property, valueInWiki, format)
        linkedwiki.loadStyles()
        return self:printDateInWiki(valueInWiki, self:getValue(property),format)
    end

    function Linkedwiki:checkTitle(property, labelInWiki, tagLang)
        linkedwiki.loadStyles()
        local iriInDB = self:getValue(property)
        --[[
           local labelInDB = nil
            if not linkedwiki.isEmpty(iriInDB) then
                labelInDB = self:getValue("http://www.w3.org/2000/01/rdf-schema#label",iriInDB)
            end
        ]]
        return self:printTitleInWiki(labelInWiki, iriInDB, tagLang)
    end

    function Linkedwiki:checkUser(property, valueInWiki, tagLang)
        linkedwiki.loadStyles()
        return self:printUserInWiki(valueInWiki, self:getValue(property), tagLang)
    end

    -- checkLabelOfInternLink
    function Linkedwiki:checkLabelOfInternLink(link, propertyOfLabel, labelInWiki, tagLang)
        linkedwiki.loadStyles()
        return self:printLinkInWiki(labelInWiki, self:getString(propertyOfLabel, tagLang), link)
    end

    -- deprecated
    function Linkedwiki:checkLink(link, property, valueInWiki, tagLang)
        linkedwiki.loadStyles()
        return self:printLinkInWiki(valueInWiki, self:getString(property, tagLang), link)
    end

    -- checkIriOfExternLink
    function Linkedwiki:checkIriOfExternLink(labelOfExternLink, propertyOfExternLink, externLinkInWiki)
        linkedwiki.loadStyles()
        return self:printExternLinkInWiki(externLinkInWiki, self:getValue(propertyOfExternLink), labelOfExternLink)
    end

    -- deprecated
    function Linkedwiki:checkExternLink(label, property, valueInWiki)
        linkedwiki.loadStyles()
        return self:printExternLinkInWiki(valueInWiki, self:getString(property), label)
    end

    return Linkedwiki
end



-- END CLASS

linkedwiki.Linkedwiki = Linkedwiki

function linkedwiki.setupInterface(options)
    -- Remove setup function
    linkedwiki.setupInterface = nil

    -- Do any other setup here

    php = mw_interface
    mw_interface = nil

    -- Install into the mw global
    mw = mw or {}
    mw.linkedwiki = mw.linkedwiki or {}

    -- Indicate that we're loaded
    package.loaded['mw.linkedwiki'] = linkedwiki
end

return linkedwiki
