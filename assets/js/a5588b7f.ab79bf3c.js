"use strict";(self.webpackChunkdatakit_docs=self.webpackChunkdatakit_docs||[]).push([[35],{1520:(e,t,a)=>{a.r(t),a.d(t,{assets:()=>d,contentTitle:()=>i,default:()=>h,frontMatter:()=>r,metadata:()=>o,toc:()=>l});var s=a(4848),n=a(8453);const r={title:"Create a data source",sidebar:"auto"},i="Create a data source",o={id:"Data-sources/create-a-data-source",title:"Create a data source",description:"DataKit supports different data sources. Out of the box it provides sources for different form plugins like Gravity",source:"@site/docs/Data-sources/10-create-a-data-source.md",sourceDirName:"Data-sources",slug:"/Data-sources/create-a-data-source",permalink:"/Data-sources/create-a-data-source",draft:!1,unlisted:!1,editUrl:"https://github.com/UseDataKit/SDK/edit/main/docs/Data-sources/10-create-a-data-source.md",tags:[],version:"current",sidebarPosition:10,frontMatter:{title:"Create a data source",sidebar:"auto"},sidebar:"docs",previous:{title:"JavaScript events",permalink:"/javascript-events"},next:{title:"ArrayDataSource",permalink:"/Data-sources/array-data-source"}},d={},l=[{value:"Read-only data source",id:"read-only-data-source",level:2},{value:"Identify the data source type",id:"identify-the-data-source-type",level:3},{value:"Retrieving (paginated) results",id:"retrieving-paginated-results",level:3},{value:"Calculating pagination results",id:"calculating-pagination-results",level:3},{value:"Filtering &amp; sorting results",id:"filtering--sorting-results",level:3},{value:"Filters",id:"filters",level:4},{value:"Searching",id:"searching",level:4},{value:"Sorting",id:"sorting",level:4},{value:"Retrieving data source fields",id:"retrieving-data-source-fields",level:3},{value:"Mutable data source",id:"mutable-data-source",level:2},{value:"Deleting a result",id:"deleting-a-result",level:3},{value:"Composing a data source",id:"composing-a-data-source",level:2}];function c(e){const t={a:"a",blockquote:"blockquote",code:"code",em:"em",h1:"h1",h2:"h2",h3:"h3",h4:"h4",header:"header",li:"li",p:"p",pre:"pre",strong:"strong",ul:"ul",...(0,n.R)(),...e.components};return(0,s.jsxs)(s.Fragment,{children:[(0,s.jsx)(t.header,{children:(0,s.jsx)(t.h1,{id:"create-a-data-source",children:"Create a data source"})}),"\n",(0,s.jsxs)(t.p,{children:["DataKit supports different data sources. Out of the box it provides sources for different form plugins like Gravity\nForms, a ",(0,s.jsx)(t.a,{href:"/Data-sources/csv-data-source",children:"CSV data source"})," and an ",(0,s.jsx)(t.a,{href:"/Data-sources/array-data-source",children:"in-memory array data source"}),". But\nyou can also create your own data source, and hook it up to a DataView."]}),"\n",(0,s.jsx)(t.h2,{id:"read-only-data-source",children:"Read-only data source"}),"\n",(0,s.jsx)(t.p,{children:"There are two types of data sources, read-only and mutable. First lets look at the read-only data source, as it is the\nbasis for the mutable data source as well."}),"\n",(0,s.jsxs)(t.p,{children:["To get started, you can either create a new class that implements the ",(0,s.jsx)(t.code,{children:"DataKit\\DataViews\\Data\\DataSource"})," interface, or\nbetter yet; create a new class that ",(0,s.jsx)(t.em,{children:"extends"})," the ",(0,s.jsx)(t.code,{children:"DataKit\\DataViews\\Data\\BaseDataSource"})," abstract class. This class\nalready implements 3 methods, namely: ",(0,s.jsx)(t.code,{children:"filter_by"}),", ",(0,s.jsx)(t.code,{children:"sort_by"})," and ",(0,s.jsx)(t.code,{children:"search_by"}),". These methods store their respective\nvalues on the class to be used by the other methods later on."]}),"\n",(0,s.jsx)(t.h3,{id:"identify-the-data-source-type",children:"Identify the data source type"}),"\n",(0,s.jsxs)(t.p,{children:["A data source needs to be able to be differentiated from other sources types. For this purpose we implement the ",(0,s.jsx)(t.code,{children:"id"}),"\nmethod, which returns a unique and consistent identifier (string) for your data source. The ",(0,s.jsx)(t.code,{children:"CsvDataSource"})," for example\nreturns the value ",(0,s.jsx)(t.code,{children:"csv-{filename}"}),", where the ",(0,s.jsx)(t.code,{children:"filename"})," differentiates between CsvDataSource instances."]}),"\n",(0,s.jsx)(t.pre,{children:(0,s.jsx)(t.code,{className:"language-php",children:"public function id() : string {\n    return 'custom';\n}\n"})}),"\n",(0,s.jsx)(t.h3,{id:"retrieving-paginated-results",children:"Retrieving (paginated) results"}),"\n",(0,s.jsxs)(t.p,{children:["A data source mostly revolves around 2 methods. First there\nis ",(0,s.jsx)(t.code,{children:"get_data_ids( int $limit = 20, int $offset = 0 ) : array"}),".\nThis method should return an array of strings, that represent unique ID's for every result. It should also take into\naccount the ",(0,s.jsx)(t.code,{children:"$limit"})," and ",(0,s.jsx)(t.code,{children:"$offset"}),", as well as any filters, sorting and search query stored by the aforementioned\nmethods."]}),"\n",(0,s.jsxs)(t.p,{children:["Secondly there is ",(0,s.jsx)(t.code,{children:"get_data_by_id( string $id ) : array"})," which should return the data for the result based on this ID.\nIf the result is not found, the method should throw a ",(0,s.jsx)(t.code,{children:"DataNotFoundException"}),"."]}),"\n",(0,s.jsx)(t.p,{children:"The reason for the separation of these methods is so that every data source is able to return the data in the same way.\nEven if it uses an API that has a separate endpoint to retrieve the data."}),"\n",(0,s.jsxs)(t.p,{children:["The array ",(0,s.jsx)(t.code,{children:"get_data_by_id()"})," returns should be a key/value-pair, where both the key and the value are a ",(0,s.jsx)(t.code,{children:"string"}),".\nThe ",(0,s.jsx)(t.code,{children:"key"})," is what is referenced by a Field ID. For example, if we want to show a field with a name of ",(0,s.jsx)(t.code,{children:"email"}),", it will\nlook for a ",(0,s.jsx)(t.code,{children:"key"})," by the name of ",(0,s.jsx)(t.code,{children:"email"})," on the array."]}),"\n",(0,s.jsxs)(t.blockquote,{children:["\n",(0,s.jsxs)(t.p,{children:[(0,s.jsx)(t.strong,{children:"Tip:"})," The methods ",(0,s.jsx)(t.code,{children:"get_data_ids()"})," and ",(0,s.jsx)(t.code,{children:"get_data_by_id()"})," are usually called in rapid succession,\nbecause ",(0,s.jsx)(t.code,{children:"get_data_by_id()"})," will be called for every result of ",(0,s.jsx)(t.code,{children:"get_data_ids()"}),". If the data source is able to retrieve\nall the results with a single query, it could be wise to retrieve all these results beforehand on the ",(0,s.jsx)(t.code,{children:"get_data_ids()"}),"\ncall, and micro cache these results in memory. That way the ",(0,s.jsx)(t.code,{children:"get_data_by_id()"})," method does not need te perform a query\nper ID."]}),"\n"]}),"\n",(0,s.jsx)(t.h3,{id:"calculating-pagination-results",children:"Calculating pagination results"}),"\n",(0,s.jsxs)(t.p,{children:["To be able to show the pagination options based on the limit and offset, we need to know the total amount of records.\nFor this the ",(0,s.jsx)(t.code,{children:"public function count(): int"})," should return this amount, while also taking into consideration the applied\nfilters and search query."]}),"\n",(0,s.jsx)(t.h3,{id:"filtering--sorting-results",children:"Filtering & sorting results"}),"\n",(0,s.jsxs)(t.p,{children:["As we mentioned, the ",(0,s.jsx)(t.code,{children:"filter_by"}),", ",(0,s.jsx)(t.code,{children:"sort_by"})," and ",(0,s.jsx)(t.code,{children:"search_by"})," methods (should) keep track of these values, to be used\nwhile retrieving the result ids in ",(0,s.jsx)(t.code,{children:"get_data_ids()"}),"."]}),"\n",(0,s.jsx)(t.h4,{id:"filters",children:"Filters"}),"\n",(0,s.jsxs)(t.p,{children:["If filters are applied, you can use the ",(0,s.jsx)(t.code,{children:"Filters"})," object to filter your results. The ",(0,s.jsx)(t.code,{children:"Filters"})," object is an iterable\nclass that returns ",(0,s.jsx)(t.code,{children:"Filter"})," objects, which in turn can be turned into an array. Alternatively the ",(0,s.jsx)(t.code,{children:"Filters"})," object can\nalso be turned into a usable array. In both cases you call the ",(0,s.jsx)(t.code,{children:"to_array"})," method on the object."]}),"\n",(0,s.jsx)(t.pre,{children:(0,s.jsx)(t.code,{className:"language-php",children:"private function data() : array {\n    $filters = $this->filters->to_array();\n    //or\n    foreach( $filters as $filter) {\n        $filter_array = $filter->to_array();\n    }\n}\n"})}),"\n",(0,s.jsxs)(t.p,{children:["The ",(0,s.jsx)(t.code,{children:"Filter"})," array contains the following keys:"]}),"\n",(0,s.jsxs)(t.ul,{children:["\n",(0,s.jsxs)(t.li,{children:[(0,s.jsx)(t.code,{children:"field"})," a ",(0,s.jsx)(t.code,{children:"string"})," representing the field name to filter against."]}),"\n",(0,s.jsxs)(t.li,{children:[(0,s.jsx)(t.code,{children:"value"})," which is either a string (for ",(0,s.jsx)(t.code,{children:"is"})," and ",(0,s.jsx)(t.code,{children:"isNot"})," operations), or an array of strings for the other operations."]}),"\n",(0,s.jsxs)(t.li,{children:[(0,s.jsx)(t.code,{children:"operation"})," a ",(0,s.jsx)(t.code,{children:"string"})," representing the filter operation type:","\n",(0,s.jsxs)(t.ul,{children:["\n",(0,s.jsxs)(t.li,{children:[(0,s.jsx)(t.code,{children:"is"})," should return a result where the ",(0,s.jsx)(t.code,{children:"field"})," equals ",(0,s.jsx)(t.code,{children:"value"})]}),"\n",(0,s.jsxs)(t.li,{children:[(0,s.jsx)(t.code,{children:"isNot"})," should return a result where the ",(0,s.jsx)(t.code,{children:"field"})," does not equal ",(0,s.jsx)(t.code,{children:"value"})]}),"\n",(0,s.jsxs)(t.li,{children:[(0,s.jsx)(t.code,{children:"isAny"})," should return a result where the field is any of the provided values"]}),"\n",(0,s.jsxs)(t.li,{children:[(0,s.jsx)(t.code,{children:"isAll"})," should return a result where the field is has all of the provided values"]}),"\n",(0,s.jsxs)(t.li,{children:[(0,s.jsx)(t.code,{children:"isNone"})," should return a result where the field is none of the provided values"]}),"\n",(0,s.jsxs)(t.li,{children:[(0,s.jsx)(t.code,{children:"isNotAll"})," should return a result where the field is does not have all of the provided values"]}),"\n"]}),"\n"]}),"\n"]}),"\n",(0,s.jsx)(t.h4,{id:"searching",children:"Searching"}),"\n",(0,s.jsxs)(t.p,{children:["If the ",(0,s.jsx)(t.code,{children:"search"})," parameter has a value, the results should be filtered based on this value. How you implement this is up\nto you. Fuzzy search results are allowed, even encouraged. But if you only want to allow strict values, that is fine."]}),"\n",(0,s.jsx)(t.h4,{id:"sorting",children:"Sorting"}),"\n",(0,s.jsxs)(t.p,{children:["The sorting of the data source is provided on the ",(0,s.jsx)(t.code,{children:"sort_by"})," method. It should change the order of the ID's returned\nby ",(0,s.jsx)(t.code,{children:"get_data_ids()"}),". The ",(0,s.jsx)(t.code,{children:"Sort"})," object can also be transformed into an array by calling the ",(0,s.jsx)(t.code,{children:"to_array()"})," method. This\narray contains the following keys:"]}),"\n",(0,s.jsxs)(t.ul,{children:["\n",(0,s.jsxs)(t.li,{children:[(0,s.jsx)(t.code,{children:"field"})," a ",(0,s.jsx)(t.code,{children:"string"})," representing the field key to sort by"]}),"\n",(0,s.jsxs)(t.li,{children:[(0,s.jsx)(t.code,{children:"direction"})," either ",(0,s.jsx)(t.code,{children:"ASC"})," (ascending) or ",(0,s.jsx)(t.code,{children:"DESC"})," (descending)"]}),"\n"]}),"\n",(0,s.jsx)(t.pre,{children:(0,s.jsx)(t.code,{className:"language-php",children:"private function data() : array {\n    // ...\n    $sorting = $this->sort->to_array(); \n}\n"})}),"\n",(0,s.jsx)(t.h3,{id:"retrieving-data-source-fields",children:"Retrieving data source fields"}),"\n",(0,s.jsxs)(t.p,{children:["In order to know the available fields for a data source, it needs to provide these fields from the ",(0,s.jsx)(t.code,{children:"get_fields()"}),"\nmethod. These results are (going to be) used by the DataView Builder UI. Therefor the result should be a key/value-pair\nof the field name, and a human-readable label."]}),"\n",(0,s.jsx)(t.pre,{children:(0,s.jsx)(t.code,{className:"language-php",children:"public function get_fields() : array {\n    return [\n        'email' => 'Email Address',\n        'name' => 'Full Name',\n    ];\n}\n"})}),"\n",(0,s.jsx)(t.h2,{id:"mutable-data-source",children:"Mutable data source"}),"\n",(0,s.jsxs)(t.p,{children:["As DataKit matures, more features will be added. One of the first features we did want to address right away, is the\nability to delete a result. For this we introduced the ",(0,s.jsx)(t.code,{children:"MutableDataSource"}),". As the name suggests, this data source can\napply changes on its data; it is ",(0,s.jsx)(t.a,{href:"https://en.wiktionary.org/wiki/mutable",children:"mutable"}),"."]}),"\n",(0,s.jsx)(t.h3,{id:"deleting-a-result",children:"Deleting a result"}),"\n",(0,s.jsxs)(t.p,{children:["After implementing the ",(0,s.jsx)(t.code,{children:"MutableDataSource"})," on the data source class, you need to implement the\n",(0,s.jsx)(t.code,{children:"public function delete_data_by_id( string ...$ids ) : void;"})," method. As you can see it allows you to provide multiple\nid's to be removed, via the spread operator (",(0,s.jsx)(t.code,{children:"..."}),"). This means the method is able to delete a single result, as well as\nmultiple results at once (depending on the backing implementation)."]}),"\n",(0,s.jsxs)(t.p,{children:["Notice that the return type is ",(0,s.jsx)(t.code,{children:"void"}),". This means DataKit will assume the deletion was successful, unless it encounters\na ",(0,s.jsx)(t.code,{children:"DataNotFoundException"}),"."]}),"\n",(0,s.jsx)(t.pre,{children:(0,s.jsx)(t.code,{className:"language-php",children:"use DataKit\\DataViews\\Data\\Exception\\DataNotFoundException;\n\npublic function delete_data_by_id( string ...$ids ) : void {\n    try {\n        DataSourceApi::delete_by_ids( $ids );\n    } catch ( NotFoundException $e ){\n        throw new DataNotFoundException( $this, $e->getMessage(), 404, $e );\n    }\n    \n    // or\n    foreach ( $ids as $id ) {\n        if (! DataSourceApi::has( $id )) {\n            throw new DataNotFoundException( $this, $e->getMessage(), 404, $e );\n        }\n        \n        DataSourceApi::delete_by_id($id);\n    }\n}\n"})}),"\n",(0,s.jsxs)(t.p,{children:["Notice how in this example we provide a reference to the current data source (",(0,s.jsx)(t.code,{children:"$this"}),") on the ",(0,s.jsx)(t.code,{children:"DataNotFoundException"}),".\nThis is useful for logging purposes, for example."]}),"\n",(0,s.jsx)(t.h2,{id:"composing-a-data-source",children:"Composing a data source"}),"\n",(0,s.jsxs)(t.p,{children:["By default, DataKit only provides either ",(0,s.jsx)(t.code,{children:"final"})," or ",(0,s.jsx)(t.code,{children:"abstract"})," classes. This makes it easier for us to add new features,\nand change implementation details; without introducing breaking changes to the end user. We believe in composition over\ninheritance, which is why we provide interfaces and a ",(0,s.jsx)(t.code,{children:"DataSourceDecorator"})," to aid in that process."]}),"\n",(0,s.jsxs)(t.p,{children:["We understand that you might have a data source that only has a fixed set of data, and does not support pagination\nout-of-the-box. This means you could use the ",(0,s.jsx)(t.code,{children:"ArrayDataSource"})," to add most of the functionality, except for the data."]}),"\n",(0,s.jsx)(t.p,{children:"Here is an example of how you could create such a data source with composition."}),"\n",(0,s.jsx)(t.pre,{children:(0,s.jsx)(t.code,{className:"language-php",children:"use DataKit\\DataViews\\Data\\DataSource;\nuse DataKit\\DataViews\\Data\\ArrayDataSource;\nuse DataKit\\DataViews\\Data\\DataSourceDecorator;\n\n// Extend the abstract datasource decorator to proxy most methods. \nfinal class CustomDataSource extends DataSourceDecorator {\n\t/**\n\t * Property to memoize the inner data source.\n\t * @var ArrayDataSource\n\t */\n\tprivate ArrayDataSource $inner;\n\n\tpublic function __construct( ...$arguments ) {\n\t\t// Create an instance with the necessary arguments and dependencies, but don't retrieve the results yet!\n\t\t// This instance might only be used to show the name on the UI, or not even used on the current page,\n\t\t// so retrieving results can be premature here.\n\t}\n\n\tpublic function id() : string {\n\t\treturn 'custom';\n\t}\n\n\tprotected function decorated_datasource() : DataSource {\n\t\t// We already instantiated the\n\t\tif ( isset( $this->inner ) ) {\n\t\t\treturn $this->inner;\n\t\t}\n\n\t\t// Retrieve the results\n\t\t$results = get_results_from_api_call();\n\n\t\t// Instantiate and memoize the inner data source for future calls.\n\t\treturn $this->inner = new ArrayDataSource( $this->id(), $results );\n\t}\n}\n"})})]})}function h(e={}){const{wrapper:t}={...(0,n.R)(),...e.components};return t?(0,s.jsx)(t,{...e,children:(0,s.jsx)(c,{...e})}):c(e)}},8453:(e,t,a)=>{a.d(t,{R:()=>i,x:()=>o});var s=a(6540);const n={},r=s.createContext(n);function i(e){const t=s.useContext(r);return s.useMemo((function(){return"function"==typeof e?e(t):{...t,...e}}),[t,e])}function o(e){let t;return t=e.disableParentContext?"function"==typeof e.components?e.components(n):e.components||n:i(e.components),s.createElement(r.Provider,{value:t},e.children)}}}]);