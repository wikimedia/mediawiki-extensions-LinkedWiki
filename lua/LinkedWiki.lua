--[[
	Registers and defines functions to access LinkedWiki through the Scribunto extension
	Provides Lua setupInterface
	@since 
	@licence CC-by-nc-sa V3.0
	@author Karima Rafes <karima.rafes@bordercloud.com>
]]

-- module
local linkedwiki = {}
local php

function linkedwiki.isEmpty(s)
    return s == nil or s == ''
end

function linkedwiki.info()
    local wikitext = mw.html.create('div')
    wikitext:addClass("plainlinks")
    wikitext:wikitext(php.info())
    return tostring(wikitext)
end

function linkedwiki.timeStamp(dateStringArg)
    local patternDateTime = '^(%d%d%d%d)-(%d?%d)-(%d?%d)T(%d?%d):(%d?%d):(%d?%d)(.-)$';
    local patternDate = '^(%d%d%d%d)-(%d?%d)-(%d?%d)$';
    local returnTime = 0
    if  string.find(dateStringArg, patternDateTime) then
        mw.log("dateCoucou")
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
    return php.setConfig(iriDataset)
end
function linkedwiki.getConfig()
    return php.getConfig()
end
function linkedwiki.getDefaultConfig()
    return php.getDefaultConfig()
end

function linkedwiki.setEndpoint(urlEndpoint)
    return php.setEndpoint(urlEndpoint)
end

function linkedwiki.setDebug(boolDebug)
    return php.setDebug(boolDebug)
end
function linkedwiki.isDebug()
--    local result =""
--    if linkedwiki.isEmpty(tagLang) then
--        result = php.getLang()
--    else
--        result = tagLang
--    end
--    return result
return php.isDebug()
end

--function linkedwiki.setGraph(iriGraph)
--    return php.setGraph(iriGraph)
--end

function linkedwiki.setSubject(iriSubject)
    return php.setSubject(iriSubject)
end

function linkedwiki.setLang(tagLang)
    return php.setLang(tagLang)
end

function linkedwiki.getLang(tagLang)
--    local result =""
--    if linkedwiki.isEmpty(tagLang) then
--        result = php.getLang()
--    else
--        result = tagLang
--    end
--    return result
    return php.getLang()
end

function linkedwiki.getLastQuery()
    return php.getLastQuery()
end

function linkedwiki.getValue(iriProperty, iriSubject)
    return php.getValue(iriProperty, iriSubject)
end

function linkedwiki.getString(iriProperty, tagLang, iriSubject)
    --checkTypeMulti( 'getString', 1, tagLang, { 'string', 'nil' } )
    return php.getString(iriProperty, tagLang, iriSubject)
end

function linkedwiki.addPropertyWithIri(iriProperty, iriValue, iriSubject)
    return php.addPropertyWithIri(iriProperty, iriValue, iriSubject)
end

function linkedwiki.addPropertyWithLitteral(iriProperty, value, type, tagLang, iriSubject)
    return php.addPropertyWithLitteral(iriProperty, value, type, tagLang, iriSubject)
end

function linkedwiki.removeSubject(iriSubject)
    return php.removeSubject(iriSubject)
end

function linkedwiki.loadData(titles)
    return php.loadData(titles)
end

local currentFullPageName
function linkedwiki.getCurrentTitle()
    local currentFullPageName
    if currentFullPageName == nil then
        currentFullPageName = mw.getCurrentFrame():preprocess('{{FULLPAGENAME}}')
    end
    return mw.title.new(currentFullPageName)
end

function linkedwiki.getCurrentIRI()
    return linkedwiki.getCurrentTitle():fullUrl()
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


function linkedwiki.concatWithComma(tab)
    local html = ""
    local comma = ""
    for key, value in pairs(tab) do
        html = html .. comma .. value
        comma=", "
    end
    return html
end


function linkedwiki.buildDiv(countInWiki,tabHtmlInWiki,countInDB,tabHtmlInDB,tabTitleInDB)
    local result = ""
    local html = ""
    local html2 = ""
    local div = mw.html.create('div')

    if countInWiki > 0 then
        html = html .. linkedwiki.concatWithComma(tabHtmlInWiki)
        div:addClass("linkedwiki_new_value")
        if countInDB > 0  then
            html = html .. ", " .. linkedwiki.concatWithComma(tabHtmlInDB)
            div:addClass("linkedwiki_tooltip")
            div:attr("data-toggle", "tooltip")
            div:attr("data-placement", "bottom")
            html2 = linkedwiki.concatWithComma(tabTitleInDB)
            div:attr("title", "Currently in DB : " .. html2)
        end
    elseif countInDB > 0 then
        div:addClass("linkedwiki_value_equal")
        html = html .. linkedwiki.concatWithComma(tabHtmlInDB)
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
        if self.databaseIsUpdate and not linkedwiki.isEmpty(labelInDB) then
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
        return self.debug or  linkedwiki.isDebug()
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

    function Linkedwiki:addPropertyWithLitteral(iriProperty, value, type, tagLang)
        self:initConfig()
        return  linkedwiki.addPropertyWithLitteral(iriProperty, value, type, tagLang)
    end
    function Linkedwiki:addProperty(iriProperty, value, type)
        return  self:addPropertyWithLitteral(iriProperty, value,type,'')
    end

    function Linkedwiki:addPropertyString(iriProperty, value, tagLang)
        return  self:addPropertyWithLitteral(iriProperty, value, nil, tagLang)
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
        local cleanId =""
        local cleanTitle =""
        local idInWiki=""

        if not linkedwiki.isEmpty(valueInDB) then
            listIri = linkedwiki.explode(";", valueInDB)
            for i, iri in ipairs(listIri) do
                self:initConfig()
                titleInDB = linkedwiki.getString("http://www.w3.org/2000/01/rdf-schema#label", tagLang, iri)

                --text = '<span class="plainlinks">[' .. iri .. ' ' .. titleInDB .. ']</span>'
                cleanId = string.match(iri, "(Q.*)")
                --mw.log(iri)
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
                countInDB = countInDB + 1
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
                --mw.log(iri)
                if not linkedwiki.isEmpty(cleanId) then
                    iriInWikidata = "http://www.wikidata.org/entity/" .. cleanId
                    wikidata = linkedwiki.new(iriInWikidata,"http://www.wikidata.org",self:getLang(tagLang))
                    cleanTitle = wikidata:getString("http://www.w3.org/2000/01/rdf-schema#label", self:getLang(tagLang))
                    text = '<span class="plainlinks">'
                            .. '[https://www.wikidata.org/wiki/Special:GoToLinkedPage/'.. self:getLang(tagLang)
                            ..'wiki/' ..cleanId
                            ..' '
                            ..  cleanTitle
                            .. ']</span>'
                    text = text.. '<span class="plainlinks"><small>([' .. iriInWikidata .. ' '..cleanId..'])</small></span>'
                else -- it is not a ID
                    cleanTitle = idInWiki
                    iriInWikidata = idInWiki
                    text = idInWiki
                end

                if not tabIriInDB[iriInWikidata] then
                    tabHtmlInWiki[iriInWikidata]= text
                    countInWiki = countInWiki +1
                elseif tabTitleInDB[iriInWikidata] ~= cleanTitle then
                    tabHtmlInWiki[iriInWikidata]= text
                    countInWiki = countInWiki +1
                end
            end
        end

        if countInWiki > 0 then
            self.databaseIsUpdate = false
        end

        return linkedwiki.buildDiv(countInWiki,tabHtmlInWiki,countInDB,tabHtmlInDB,tabTitleInDB)
    end
    --function Linkedwiki:printItemInWiki(valueInWiki, valueInDB, tagLang)
    --    local div = mw.html.create('div')
    --    local listLink = ""
    --
    --    if not linkedwiki.isEmpty(valueInDB) then
    --        local listIriInDBLocal = linkedwiki.explode(";", valueInDB)
    --        for i, iri in ipairs(listIriInDBLocal) do
    --            listLink = listLink .. '[https://www.wikidata.org/wiki/Special:GoToLinkedPage/enwiki/' .. string.match(iri, "(Q.*)") .. " " .. linkedwiki.getString("http://www.w3.org/2000/01/rdf-schema#label", nil, iri) .. "] "
    --        end
    --    end
    --
    --    if not linkedwiki.isEmpty(valueInWiki) then
    --        div:wikitext(listLink .. valueInWiki)
    --
    --        self.databaseIsUpdate = false
    --        div:addClass("linkedwiki_new_value")
    --        if not linkedwiki.isEmpty(valueInDB) then
    --            div:addClass("linkedwiki_tooltip")
    --            div:attr("data-toggle", "tooltip")
    --            div:attr("data-placement", "bottom")
    --            div:attr("title", "Currently in DB : " .. valueInDB)
    --        end
    --    elseif not linkedwiki.isEmpty(valueInDB) then
    --        div:wikitext(listLink)
    --    end
    --    return tostring(div)
    --end


    function Linkedwiki:printValueInWiki(valueInWiki, valueInDB)
        local div = mw.html.create('div')

        if not linkedwiki.isEmpty(valueInWiki) then
            div:wikitext(valueInWiki)

            if valueInWiki == valueInDB then
                div:addClass("linkedwiki_value_equal")
            else
                self.databaseIsUpdate = false
                div:addClass("linkedwiki_new_value")
                if not linkedwiki.isEmpty(valueInDB) then
                    div:addClass("linkedwiki_tooltip")
                    div:attr("data-toggle", "tooltip")
                    div:attr("data-placement", "bottom")
                    div:attr("title", "Currently in DB : " .. valueInDB)
                end
            end
        elseif not linkedwiki.isEmpty(valueInDB) then
            div:wikitext(valueInDB)
        end
        return tostring(div)
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
                elseif tabTitleInDB[iriInWiki] ~= cleanTitle then
                    tabHtmlInWiki[iriInWiki]= text
                    countInWiki = countInWiki +1
                end
            end
        end

        if countInWiki > 0 then
            self.databaseIsUpdate = false
        end

        return linkedwiki.buildDiv(countInWiki,tabHtmlInWiki,countInDB,tabHtmlInDB,tabTitleInDB)
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
                elseif tabTitleInDB[iriInWiki] ~= cleanTitle then
                    tabHtmlInWiki[iriInWiki]= text
                    countInWiki = countInWiki +1
                end
            end
        end

        if countInWiki > 0 then
            self.databaseIsUpdate = false
        end

        return linkedwiki.buildDiv(countInWiki,tabHtmlInWiki,countInDB,tabHtmlInDB,tabTitleInDB)
    end

    function Linkedwiki:printImageInWiki(valueInWiki, valueInDB, width, height)
        -- todo insert width, height
        local div = mw.html.create('div')
        local img = mw.html.create('img')

        if not linkedwiki.isEmpty(width) then
            img:css( "width",tostring(width) .. 'px')
        end
        if not linkedwiki.isEmpty(height) then
            img:css( "height",tostring(height) .. 'px')
        end

        if not linkedwiki.isEmpty(valueInWiki) then
            img:attr("src", valueInWiki)
            div:node(img)

            if valueInWiki == valueInDB then
                div:addClass("linkedwiki_value_equal")
            else
                self.databaseIsUpdate = false
                div:addClass("linkedwiki_new_value")
                if not linkedwiki.isEmpty(valueInDB) then
                    div:addClass("linkedwiki_tooltip")
                    div:attr("data-toggle", "tooltip")
                    div:attr("data-placement", "bottom")
                    div:attr("title", "Currently in DB : " .. valueInDB)
                end
            end
        elseif not linkedwiki.isEmpty(valueInDB) then
            img:attr("src", valueInDB)
            div:node(img)
        else -- Empty
            return ''
        end
        return tostring(div)
    end

    function Linkedwiki:printDateInWiki(valueInWiki, valueInDB,format)
        local div = mw.html.create('div')

        if not linkedwiki.isEmpty(valueInWiki) then
            local timeStampInWiki = linkedwiki.timeStamp(valueInWiki)
            if linkedwiki.isEmpty(timeStampInWiki) then
                div:wikitext('Error is not a date (0000-00-00) : ' .. valueInWiki)
            else
                div:wikitext(mw.getCurrentFrame():preprocess('{{#time:' .. format .. '|' .. valueInWiki .. '}}'))
                if not linkedwiki.isEmpty(valueInDB) then
                    local timeStampInDB = linkedwiki.timeStamp(valueInDB)
                    if linkedwiki.timeStamp(valueInWiki) == timeStampInDB then
                        div:addClass("linkedwiki_value_equal")
                    else
                        self.databaseIsUpdate = false
                        div:addClass("linkedwiki_new_value")
                        if not linkedwiki.isEmpty(valueInDB) then
                            div:addClass("linkedwiki_tooltip")
                            div:attr("data-toggle", "tooltip")
                            div:attr("data-placement", "bottom")
                            if linkedwiki.isEmpty(timeStampInDB) then
                                div:attr("title", "Currently in DB : not a date")
                            else
                                div:attr("title", "Currently in DB : " ..
                                    mw.getCurrentFrame():preprocess('{{#time:' .. format .. '|' .. valueInDB .. '}}'))
                            end
                        end
                    end
                end
            end
        elseif not linkedwiki.isEmpty(valueInDB) then
            div:wikitext(mw.getCurrentFrame():preprocess('{{#time:' .. format .. '|' .. valueInDB .. '}}'))
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
                div:addClass("linkedwiki_value_equal")
            else
                self.databaseIsUpdate = false
                div:addClass("linkedwiki_new_value")
                if not linkedwiki.isEmpty(valueInDB) then
                    div:addClass("linkedwiki_tooltip")
                    div:attr("data-toggle", "tooltip")
                    div:attr("data-placement", "bottom")
                    div:attr("title", "Currently in DB : " .. valueInDB)
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
                div:addClass("linkedwiki_value_equal")
            else
                self.databaseIsUpdate = false
                div:addClass("linkedwiki_new_value")
                if not linkedwiki.isEmpty(valueInDB) then
                    div:addClass("linkedwiki_tooltip")
                    div:attr("data-toggle", "tooltip")
                    div:attr("data-placement", "bottom")
                    div:attr("title", "Currently in DB : " .. valueInDB)
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
        return self:printValueInWiki(valueInWiki, self:getValue(property))
    end

    function Linkedwiki:checkString(property, valueInWiki, tagLang)
        return self:printValueInWiki(valueInWiki, self:getString(property, tagLang))
    end

    function Linkedwiki:checkItem(property, valueInWiki, tagLang)
        return self:printItemInWiki(valueInWiki, self:getValue(property), tagLang)
    end

    function Linkedwiki:checkImage(property, valueInWiki, width, height)
        return self:printImageInWiki(valueInWiki, self:getValue(property), width, height)
    end

    function Linkedwiki:checkDate(property, valueInWiki, format)
        return self:printDateInWiki(valueInWiki, self:getValue(property),format)
    end

    function Linkedwiki:checkTitle(property, labelInWiki, tagLang)
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
        return self:printUserInWiki(valueInWiki, self:getValue(property), tagLang)
    end

    -- checkLabelOfInternLink
    function Linkedwiki:checkLabelOfInternLink(link, propertyOfLabel, labelInWiki, tagLang)
        return self:printLinkInWiki(labelInWiki, self:getString(propertyOfLabel, tagLang), link)
    end

    -- deprecated
    function Linkedwiki:checkLink(link, property, valueInWiki, tagLang)
        return self:printLinkInWiki(valueInWiki, self:getString(property, tagLang), link)
    end

    -- checkIriOfExternLink
    function Linkedwiki:checkIriOfExternLink(labelOfExternLink, propertyOfExternLink, externLinkInWiki)
        return self:printExternLinkInWiki(externLinkInWiki, self:getValue(propertyOfExternLink), labelOfExternLink)
    end

    -- deprecated
    function Linkedwiki:checkExternLink(label, property, valueInWiki)
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