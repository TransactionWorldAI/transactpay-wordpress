import { __experimentalInputControl as InputControl, SelectControl } from '@wordpress/components';
import { useState, useEffect } from 'react';
// import { useState } from '@wordpress/element';

export const CustomSelectControl = ({ labelName, initalValue, options  }) => {
  const [ value, setValue ] = useState( initalValue );

  return (
    <SelectControl
      label={ labelName || "{{ No Label Added }}" }
      value={ value }
      options={ options }
      onChange={ setValue }
    />
  );
};

export const InputWithSideLabel = ({ initialValue, labelName, isConfidential }) => {
   const isHidden = isConfidential || false;
   const [ value, setValue ] = useState( initialValue );
   return (
      <InputControl
         __unstableInputWidth="3em"
         label={ labelName || '{{label_name}}'}
         value={ value }
         type={ (isHidden)? 'password' : 'text' }
         labelPosition="edge"
         onChange={( nextValue ) => setValue( nextValue ?? '' )}
      />
   )
}

const Input = ({ initialValue, labelName, onChange,  isConfidential }) => {
  const isHidden = isConfidential || false;
  const [ value, setValue ] = useState( initialValue );

  const handleValueChange = (evt) => {
   onChange(evt)
   setValue(evt)
  }

  useEffect(()=>{

  },[value]);

  return (
     <InputControl
        label={ labelName || '{{label_name}}'}
        value={ value }
        type={ (isHidden)? 'password' : 'text' }
        onChange={ evt => handleValueChange(evt) }
     />
  );
};

export default Input;