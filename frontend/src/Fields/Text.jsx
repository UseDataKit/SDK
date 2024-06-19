export default function Text( { name, item, context } ) {
    return <div>{item[ name ] || ''}</div>;
}
