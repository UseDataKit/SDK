"use strict";(self.webpackChunkdatakit_docs=self.webpackChunkdatakit_docs||[]).push([[389],{3385:(e,i,a)=>{a.r(i),a.d(i,{assets:()=>r,contentTitle:()=>t,default:()=>h,frontMatter:()=>s,metadata:()=>d,toc:()=>c});var n=a(4848),l=a(8453);const s={title:"Introduction to fields"},t="Using fields",d={id:"Fields/using-fields",title:"Introduction to fields",description:"A DataView consists of fields and data. The data is provided by",source:"@site/docs/Fields/10-using-fields.md",sourceDirName:"Fields",slug:"/Fields/using-fields",permalink:"/Fields/using-fields",draft:!1,unlisted:!1,editUrl:"https://github.com/UseDataKit/SDK/edit/main/docs/Fields/10-using-fields.md",tags:[],version:"current",sidebarPosition:10,frontMatter:{title:"Introduction to fields"},sidebar:"docs",previous:{title:"CsvDataSource",permalink:"/Data-sources/csv-data-source"},next:{title:"TextField",permalink:"/Fields/text-field"}},r={},c=[{value:"What are fields?",id:"what-are-fields",level:2},{value:"Creating a field instance",id:"creating-a-field-instance",level:2},{value:"Applying field settings",id:"applying-field-settings",level:2},{value:"Change value before rendering",id:"change-value-before-rendering",level:2},{value:"A default value (fallback value)",id:"a-default-value-fallback-value",level:3},{value:"Changing the value with a callback",id:"changing-the-value-with-a-callback",level:3},{value:"Filtering",id:"filtering",level:2}];function o(e){const i={a:"a",admonition:"admonition",code:"code",em:"em",h1:"h1",h2:"h2",h3:"h3",header:"header",li:"li",p:"p",pre:"pre",strong:"strong",ul:"ul",...(0,l.R)(),...e.components};return(0,n.jsxs)(n.Fragment,{children:[(0,n.jsx)(i.header,{children:(0,n.jsx)(i.h1,{id:"using-fields",children:"Using fields"})}),"\n",(0,n.jsxs)(i.p,{children:["A DataView consists of fields and data. The data is provided by\na ",(0,n.jsx)(i.a,{href:"/Data-sources/create-a-data-source",children:"DataSource"}),",\nand the fields are provided by you. In this chapter we'll explore what a field is, and how you can create your own."]}),"\n",(0,n.jsx)(i.h2,{id:"what-are-fields",children:"What are fields?"}),"\n",(0,n.jsx)(i.p,{children:"Fields in a DataView are rendered differently depending on the Layout. For a Table view, the fields are shown as columns\non a table. However, for every layout type; the registration of the fields is the same."}),"\n",(0,n.jsx)(i.p,{children:"Currently, DataKit provides the following field types:"}),"\n",(0,n.jsxs)(i.ul,{children:["\n",(0,n.jsxs)(i.li,{children:[(0,n.jsx)(i.a,{href:"/Fields/text-field",children:(0,n.jsx)(i.code,{children:"TextField"})}),": Renders the value as plain text. Tags are stripped, and no HTML is parsed."]}),"\n",(0,n.jsxs)(i.li,{children:[(0,n.jsx)(i.a,{href:"/Fields/html-field",children:(0,n.jsx)(i.code,{children:"HtmlField"})}),": Renders the value as HTML."]}),"\n",(0,n.jsxs)(i.li,{children:[(0,n.jsx)(i.a,{href:"/Fields/datetime-field",children:(0,n.jsx)(i.code,{children:"DateTimeField"})}),": Renders the value as a date according to a provided format."]}),"\n",(0,n.jsxs)(i.li,{children:[(0,n.jsx)(i.a,{href:"/Fields/enum-field",children:(0,n.jsx)(i.code,{children:"EnumField"})}),": Renders the output based on a fixed set op possible values."]}),"\n",(0,n.jsxs)(i.li,{children:[(0,n.jsx)(i.a,{href:"/Fields/image-field",children:(0,n.jsx)(i.code,{children:"ImageField"})}),": Renders the value as a ",(0,n.jsx)(i.code,{children:"<img />"})," tag."]}),"\n",(0,n.jsxs)(i.li,{children:[(0,n.jsx)(i.a,{href:"/Fields/gravatar-field",children:(0,n.jsx)(i.code,{children:"GravatarField"})}),": Renders an email address as the ",(0,n.jsx)(i.a,{href:"https://gravatar.com/",children:"Gravatar"})," avatar\npicture."]}),"\n",(0,n.jsxs)(i.li,{children:[(0,n.jsx)(i.a,{href:"/Fields/link-field",children:(0,n.jsx)(i.code,{children:"LinkField"})}),": Renders the value as a link."]}),"\n",(0,n.jsxs)(i.li,{children:[(0,n.jsx)(i.a,{href:"/Fields/status-indicator-field",children:(0,n.jsx)(i.code,{children:"StatusIndicator"})}),": Renders the value as a status indicator (active/inactive, or with\ndifferent states)."]}),"\n"]}),"\n",(0,n.jsxs)(i.p,{children:["Every field is (and should be) extended from the abstract ",(0,n.jsx)(i.code,{children:"Field"})," class. This class provides an API that is valid\nfor every field type."]}),"\n",(0,n.jsx)(i.h2,{id:"creating-a-field-instance",children:"Creating a field instance"}),"\n",(0,n.jsx)(i.p,{children:(0,n.jsxs)(i.em,{children:["In this example we'll focus on a ",(0,n.jsx)(i.code,{children:"TextField"})," as it is the most basic field, but it should be valid for most fields."]})}),"\n",(0,n.jsxs)(i.p,{children:["To provide a fluent API, a field is created by the named constructor ",(0,n.jsx)(i.code,{children:"Field::create( string $id, string $label )"}),". You\nneed to call this method on the specific field class; calling ",(0,n.jsx)(i.code,{children:"Field::create"})," will result in an error, as the ",(0,n.jsx)(i.code,{children:"Field"}),"\nclass is abstract and cannot be instantiated."]}),"\n",(0,n.jsxs)(i.p,{children:["As you might have noticed, there is an ",(0,n.jsx)(i.code,{children:"id"})," and a ",(0,n.jsx)(i.code,{children:"label"})," for every field. The ",(0,n.jsx)(i.code,{children:"id"})," is a reference to the field name on\nthe DataSource the DataView uses. Let's assume an ",(0,n.jsx)(i.code,{children:"ArrayDataSource"})," with a ",(0,n.jsx)(i.code,{children:"name"})," key. To create a text field for the\nname you need to call:"]}),"\n",(0,n.jsx)(i.pre,{children:(0,n.jsx)(i.code,{className:"language-php",children:"use DataKit\\DataViews\\Field\\TextField;\n\n$name = TextField::create( 'name', 'Full name' );\n"})}),"\n",(0,n.jsxs)(i.p,{children:["This will create a field for the ",(0,n.jsx)(i.code,{children:"name"})," field, with a label of ",(0,n.jsx)(i.code,{children:"Full name"}),"."]}),"\n",(0,n.jsx)(i.admonition,{type:"note",children:(0,n.jsxs)(i.p,{children:["Please see the documentation for the specific field types, as for some fields there are more required parameters\non the ",(0,n.jsx)(i.code,{children:"create"})," method (e.g. the ",(0,n.jsx)(i.code,{children:"EnumField"}),")."]})}),"\n",(0,n.jsx)(i.h2,{id:"applying-field-settings",children:"Applying field settings"}),"\n",(0,n.jsxs)(i.p,{children:["After creating a field, you can finetune the settings for that particular field using a set of methods. Because\na ",(0,n.jsx)(i.code,{children:"Field"})," is ",(0,n.jsx)(i.strong,{children:"immutable"}),", every method call will return a new instance with that setting applied. This allows you to\ncreate a field with all your required settings, while being able to pass it around that instance without the\npossibility of change."]}),"\n",(0,n.jsxs)(i.p,{children:["The following methods are available on any ",(0,n.jsx)(i.code,{children:"Field"}),":"]}),"\n",(0,n.jsxs)(i.ul,{children:["\n",(0,n.jsxs)(i.li,{children:[(0,n.jsx)(i.code,{children:"->sortable()"})," (default) Makes entries sortable (Ascending / Descending) on this Field."]}),"\n",(0,n.jsxs)(i.li,{children:[(0,n.jsx)(i.code,{children:"->not_sortable()"})," Removes the ability to sort entries on this field."]}),"\n",(0,n.jsxs)(i.li,{children:[(0,n.jsx)(i.code,{children:"->hideable()"})," (default) Allows the field to be hidden on the view."]}),"\n",(0,n.jsxs)(i.li,{children:[(0,n.jsx)(i.code,{children:"->always_visible()"})," Makes this field always visible on the view; it cannot be hidden."]}),"\n",(0,n.jsxs)(i.li,{children:[(0,n.jsx)(i.code,{children:"->visible()"})," (default) Will show the field on initial load."]}),"\n",(0,n.jsxs)(i.li,{children:[(0,n.jsx)(i.code,{children:"->hidden()"})," Will not show the field on the initial load."]}),"\n",(0,n.jsxs)(i.li,{children:[(0,n.jsx)(i.code,{children:"->default_value( ?string $default_value )"})," Applies a fallback value if the field is empty on the dataset."]}),"\n",(0,n.jsxs)(i.li,{children:[(0,n.jsx)(i.code,{children:"->callback( ?callable $callback )"})," Allows changing the value before it is\nrendered. ",(0,n.jsx)(i.a,{href:"#change-value-before-rendering",children:"Read more"}),"."]}),"\n"]}),"\n",(0,n.jsx)(i.h2,{id:"change-value-before-rendering",children:"Change value before rendering"}),"\n",(0,n.jsx)(i.p,{children:"Sometimes the value that is recorded on the data sources, is not in the format you want to show on the view. Or the data\nis missing, and you want to provide a backup. For this we have two options; a default value and a callback."}),"\n",(0,n.jsx)(i.h3,{id:"a-default-value-fallback-value",children:"A default value (fallback value)"}),"\n",(0,n.jsxs)(i.p,{children:["Whenever the data source does not contain a value for a specific field, you can provide a default value to use instead.\nBy calling the ",(0,n.jsx)(i.code,{children:"->default_value()"})," method on your field creation, you can provide this value."]}),"\n",(0,n.jsx)(i.pre,{children:(0,n.jsx)(i.code,{className:"language-php",children:"$email = TextField::create( 'email', 'Email Address' )->default_value( 'Not provided' );\n"})}),"\n",(0,n.jsx)(i.h3,{id:"changing-the-value-with-a-callback",children:"Changing the value with a callback"}),"\n",(0,n.jsx)(i.p,{children:"In cases where you want to change the formatting of a value you can provide a callback method to the field. This method\nreceives the field ID, and the entire data item as a parameter. This way you can access all fields, and combine values\ninto your desired format."}),"\n",(0,n.jsxs)(i.p,{children:["In this example any value longer than 15 characters will be truncated. A result could be ",(0,n.jsx)(i.code,{children:"person@gravityk..."}),"."]}),"\n",(0,n.jsx)(i.pre,{children:(0,n.jsx)(i.code,{className:"language-php",children:"$email = TextField::create( 'email', 'Email address' )\n    ->callback( function ( string $id, array $data ) : string {\n        $value = $data[ $id ] ?? ''; // Retrieve the original value for this field.\n        if ( strlen( $value ) <= 15 ) {\n            return $value;\n        }\n\n        // Truncate any value longer than 20 characters.\n        return substr( $value, 0, 15 ) . '...';\n    } );\n"})}),"\n",(0,n.jsx)(i.admonition,{type:"note",children:(0,n.jsxs)(i.p,{children:["The callback function requires a ",(0,n.jsx)(i.code,{children:"callable"}),". This means you can also provide a callable as an array notation, e.g.\n",(0,n.jsx)(i.code,{children:"[ $this, 'my_callback' ]"})," or even an invokable class instance."]})}),"\n",(0,n.jsx)(i.p,{children:'You can even create "fake" fields by combining multiple fields into one.'}),"\n",(0,n.jsx)(i.pre,{children:(0,n.jsx)(i.code,{className:"language-php",children:"$name_email = TextField::create( 'name_email', 'Name (Email)' )\n    ->callback( function ( string $id, array $data ) : string {\n        return sprintf( '%s (%s)', $data['name'] ?? '', $data['email'] ?? '' );\n    } );\n"})}),"\n",(0,n.jsxs)(i.p,{children:["The field ",(0,n.jsx)(i.code,{children:"name_email"})," does not exist on the data set, but the callback function will make sure it will return a value\nlike ",(0,n.jsx)(i.code,{children:"Person (person@gravitykit.com)"})," on the view."]}),"\n",(0,n.jsx)(i.h2,{id:"filtering",children:"Filtering"}),"\n",(0,n.jsxs)(i.p,{children:["Fields can be made filterable. These filters are applied by the datasource. Filtering is based around a search query or\na finite set of values. These values and thus the filters are currently only available on\nan ",(0,n.jsx)(i.a,{href:"/Fields/enum-field",children:(0,n.jsx)(i.code,{children:"EnumField"})}),"."]})]})}function h(e={}){const{wrapper:i}={...(0,l.R)(),...e.components};return i?(0,n.jsx)(i,{...e,children:(0,n.jsx)(o,{...e})}):o(e)}},8453:(e,i,a)=>{a.d(i,{R:()=>t,x:()=>d});var n=a(6540);const l={},s=n.createContext(l);function t(e){const i=n.useContext(s);return n.useMemo((function(){return"function"==typeof e?e(i):{...i,...e}}),[i,e])}function d(e){let i;return i=e.disableParentContext?"function"==typeof e.components?e.components(l):e.components||l:t(e.components),n.createElement(s.Provider,{value:i},e.children)}}}]);