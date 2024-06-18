export default function Html( { name, item } ) {
    return <div dangerouslySetInnerHTML={{ __html: item[ name ] || '' }}></div>;
}
