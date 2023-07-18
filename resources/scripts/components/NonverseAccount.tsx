import Spinner from "@/components/elements/Spinner";
import {useEffect} from "react";

const NonverseAccount = () => {

    useEffect(() => {
        // @ts-ignore
        window.open('https://account.nonverse.test')
    }, [])

    return (
        <div className="awaiting-oauth absolute w-screen mt-40" style={{
            display: "flex",
            alignItems: "center",
            justifyContent: "center"
        }}>
            <Spinner/>
        </div>
    )
}

export default NonverseAccount
