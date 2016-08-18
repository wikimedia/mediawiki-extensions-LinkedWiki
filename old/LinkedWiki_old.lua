-- @copyright (c) 2016 Bourdercloud.com
-- @author Karima Rafes <karima.rafes@bordercloud.com>
-- @link http://www.mediawiki.org/wiki/Extension:LinkedWiki
-- @license CC-by-nc-sa V3.0
--
--  Last version : http://github.com/BorderCloud/LinkedWiki
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
	Registers and defines functions to access LinkedWiki through the Scribunto extension
	Provides Lua setupInterface
	@since 
	@licence CC-by-nc-sa V3.0
	@author Karima Rafes <karima.rafes@bordercloud.com>
]]

-- module
local linkedwiki = {}


local endpoint = {}
local php

local DATABASE_IS_UPDATE = true


function linkedwiki.isEmpty(s)
    return s == nil or s == ''
end

function linkedwiki.info()
    local wikitext = mw.html.create('div')
    wikitext:addClass("plainlinks")
    wikitext:wikitext(php.info())
    return tostring(wikitext)
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

function linkedwiki.setEndpoint(urlEndpoint)
    return php.setEndpoint(urlEndpoint)
end

function linkedwiki.setDebug(boolDebug)
    return php.setDebug(boolDebug)
end

function linkedwiki.setGraph(iriGraph)
    return php.setGraph(iriGraph)
end

function linkedwiki.setSubject(iriSubject)
    return php.setSubject(iriSubject)
end

function linkedwiki.setLang(tagLang)
    return php.setLang(tagLang)
end

function linkedwiki.getLang(tagLang)
    local result =""
    if linkedwiki.isEmpty(tagLang) then
        result = php.getLang()
    else
        result = tagLang
    end
    return result
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

function linkedwiki.printItemInWiki(valueInWiki, valueInDB, tagLang)
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


    if not linkedwiki.isEmpty(valueInDB) then
        listIri = linkedwiki.explode(";", valueInDB)
        for i, iri in ipairs(listIri) do
            titleInDB = linkedwiki.getString("http://www.w3.org/2000/01/rdf-schema#label", tagLang, iri)
            text = '<span class="plainlinks">[' .. iri .. ' ' .. titleInDB .. ']</span>'
            tabIriInDB[iri]= true
            tabTitleInDB[iri]= titleInDB
            tabHtmlInDB[iri]= text
            countInDB = countInDB + 1
        end
    end

    if not linkedwiki.isEmpty(valueInWiki) then
        listValue = linkedwiki.explode(";", valueInWiki)
        local wikidata = require 'linkedwiki'
        wikidata.setConfig("http://www.wikidata.org")
        for i, id in ipairs(listValue) do
            cleanId = mw.text.trim( id )
            iriInWikidata = "http://www.wikidata.org/entity/" .. cleanId
            cleanTitle = wikidata.getString("http://www.w3.org/2000/01/rdf-schema#label", tagLang, iri)
            text = '<span class="plainlinks">['
                    .. '[https://www.wikidata.org/wiki/Special:GoToLinkedPage/'.. linkedwiki.getLang(tagLang)
                    ..'wiki/' ..cleanId
                    ..' '
                    ..  cleanTitle
                    .. ']</span>'
            text = text.. '<span class="plainlinks"><small>([' .. iriInWikidata .. ' '..cleanId..'])</small></span>'

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
        DATABASE_IS_UPDATE = false
    end

    return linkedwiki.buildDiv(countInWiki,tabHtmlInWiki,countInDB,tabHtmlInDB,tabTitleInDB)
end
--function linkedwiki.printItemInWiki(valueInWiki, valueInDB, tagLang)
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
--        DATABASE_IS_UPDATE = false
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


function linkedwiki.printValueInWiki(valueInWiki, valueInDB)
    local div = mw.html.create('div')

    if not linkedwiki.isEmpty(valueInWiki) then
        div:wikitext(valueInWiki)

        if valueInWiki == valueInDB then
            div:addClass("linkedwiki_value_equal")
        else
            DATABASE_IS_UPDATE = false
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

function linkedwiki.printTitleInWiki(valueInWiki, valueInDB, tagLang)
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
            titleInDB = linkedwiki.getString("http://www.w3.org/2000/01/rdf-schema#label", tagLang, iri)
            text = '<span class="plainlinks">[' .. iri .. ' ' .. titleInDB .. ']</span>'
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
        DATABASE_IS_UPDATE = false
    end

    return linkedwiki.buildDiv(countInWiki,tabHtmlInWiki,countInDB,tabHtmlInDB,tabTitleInDB)
end

function linkedwiki.printUserInWiki(valueInWiki, valueInDB, tagLang)
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
            titleInDB = linkedwiki.getString("http://www.w3.org/2000/01/rdf-schema#label", tagLang, iri)
            text = '<span class="plainlinks">[' .. iri .. ' ' .. titleInDB .. ']</span>'

            userEmailInDB = linkedwiki.explode(";",linkedwiki.getValue("http://www.w3.org/2006/vcard/ns#email", iri))
            for i, email in ipairs(userEmailInDB) do
                text = text .. '<sub><span class="plainlinks" style="font-size: large;">[' .. email .. ' &#9993;]</span></sub>'
            end

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
        DATABASE_IS_UPDATE = false
    end

    return linkedwiki.buildDiv(countInWiki,tabHtmlInWiki,countInDB,tabHtmlInDB,tabTitleInDB)
end

function linkedwiki.printImageInWiki(valueInWiki, valueInDB, width, height)
    local div = mw.html.create('div')
    local img = mw.html.create('img')

    if not linkedwiki.isEmpty(valueInWiki) then
        img:attr("src", valueInWiki)
        div:node(img)

        if valueInWiki == valueInDB then
            div:addClass("linkedwiki_value_equal")
        else
            DATABASE_IS_UPDATE = false
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
    end
    return tostring(div)
end


function linkedwiki.printDateInWiki(format, valueInWiki, valueInDB)
    local div = mw.html.create('div')

    if not linkedwiki.isEmpty(valueInWiki) then
        div:wikitext(mw.getCurrentFrame():preprocess('{{#time:' .. format .. '|' .. valueInWiki .. '}}'))

        if valueInWiki == valueInDB then
            div:addClass("linkedwiki_value_equal")
        else
            DATABASE_IS_UPDATE = false
            div:addClass("linkedwiki_new_value")
            if not linkedwiki.isEmpty(valueInDB) then
                div:addClass("linkedwiki_tooltip")
                div:attr("data-toggle", "tooltip")
                div:attr("data-placement", "bottom")
                div:attr("title", "Currently in DB : " .. valueInDB)
            end
        end
    elseif not linkedwiki.isEmpty(valueInDB) then
        div:wikitext(mw.getCurrentFrame():preprocess('{{#time:' .. format .. '|' .. valueInDB .. '}}'))
    end
    return tostring(div)
end

function linkedwiki.printExternLinkInWiki(valueInWiki, valueInDB, label)
    local div = mw.html.create('div')

    if not linkedwiki.isEmpty(valueInWiki) then
        div:wikitext('[' .. valueInWiki .. ' ' .. label .. ']')

        if valueInWiki == valueInDB then
            div:addClass("linkedwiki_value_equal")
        else
            DATABASE_IS_UPDATE = false
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
    end
    return tostring(div)
end

function linkedwiki.printLinkInWiki(valueInWiki, valueInDB, link)
    local div = mw.html.create('div')

    if not linkedwiki.isEmpty(valueInWiki) then
        div:wikitext('<span class="plainlinks">[' .. link .. ' ' .. valueInWiki .. ']</span>')

        if valueInWiki == valueInDB then
            div:addClass("linkedwiki_value_equal")
        else
            DATABASE_IS_UPDATE = false
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
    end
    return tostring(div)
end

function linkedwiki.checkValue(property, valueInWiki)
    return linkedwiki.printValueInWiki(valueInWiki, linkedwiki.getValue(property))
end

function linkedwiki.checkString(property, valueInWiki, tagLang)
    return linkedwiki.printValueInWiki(valueInWiki, linkedwiki.getString(property, tagLang))
end

function linkedwiki.checkItem(property, valueInWiki, tagLang)
    return linkedwiki.printItemInWiki(valueInWiki, linkedwiki.getValue(property), tagLang)
end

function linkedwiki.checkImage(property, valueInWiki, width, height)
    return linkedwiki.printImageInWiki(valueInWiki, linkedwiki.getValue(property), width, height)
end

function linkedwiki.checkTitle(property, labelInWiki, tagLang)
    local iriInDB = linkedwiki.getValue(property)
    --[[
       local labelInDB = nil
        if not linkedwiki.isEmpty(iriInDB) then
            labelInDB = linkedwiki.getValue("http://www.w3.org/2000/01/rdf-schema#label",iriInDB)
        end
    ]]
    return linkedwiki.printTitleInWiki(labelInWiki, iriInDB, tagLang)
end

function linkedwiki.checkUser(property, valueInWiki, tagLang)
    return linkedwiki.printUserInWiki(valueInWiki, linkedwiki.getValue(property), tagLang)
end

-- checkLabelOfInternLink
function linkedwiki.checkLabelOfInternLink(link, propertyOfLabel, labelInWiki, tagLang)
    return linkedwiki.printLinkInWiki(labelInWiki, linkedwiki.getString(propertyOfLabel, tagLang), link)
end

-- deprecated
function linkedwiki.checkLink(link, property, valueInWiki, tagLang)
    return linkedwiki.printLinkInWiki(valueInWiki, linkedwiki.getString(property, tagLang), link)
end

-- checkIriOfExternLink
function linkedwiki.checkIriOfExternLink(labelOfExternLink, propertyOfExternLink, externLinkInWiki)
    return linkedwiki.printExternLinkInWiki(externLinkInWiki, linkedwiki.getValue(propertyOfExternLink), labelOfExternLink)
end

-- deprecated
function linkedwiki.checkExternLink(label, property, valueInWiki)
    return linkedwiki.printExternLinkInWiki(valueInWiki, linkedwiki.getString(property), label)
end

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

function linkedwiki.getMaintenanceCategory()
    local result = ''
    local labelInDB = linkedwiki.getValue("http://www.w3.org/2000/01/rdf-schema#label", linkedwiki.getCurrentIRI())
    if DATABASE_IS_UPDATE and not linkedwiki.isEmpty(labelInDB) then
        result = ""
    else
        result = "[[Category:Check data]]"
    end
    return result
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
            html = html .. linkedwiki.concatWithComma(tabHtmlInDB)
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

function linkedwiki.setupInterface(options)
    -- Remove setup function
    linkedwiki.setupInterface = nil

    -- Do any other setup here

    php = mw_interface


    -- Install into the mw global
    mw = mw or {}
    mw.linkedwiki = mw.linkedwiki or {}

    -- Indicate that we're loaded
    package.loaded['mw.linkedwiki'] = linkedwiki
end

return linkedwiki