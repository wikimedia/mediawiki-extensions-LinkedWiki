require 'page-object'

class LinkedwikiTableBasic
  include PageObject

  include URL
  page_url URL.url('<%=params[:page_name]%>')

  a(:CSV, text: 'CSV')
  a(:refresh, text: 'Refresh')
end
