/**
 * @param { string } buttonId
 * @param { import("./types").ButtonDefinition } buttonDefinition 
 */
export const makeButtonVariantDialog = (buttonId, buttonDefinition) => {
  if (buttonDefinition.variant !== 'dialog') {
    throw new Error('variant is not dialog')
  }

  const button = document.createElement('button')
  button.classList.add('clickpad2__pallet-button')
  button.dataset.id = buttonId
  button.dataset.variant = 'dialog'
  button.textContent = buttonDefinition.caption
  button.type = 'button'
  button.onclick = () => {
    // dialog を生成する
    const dialog = document.createElement('dialog')
    dialog.classList.add('clickpad2__dialog', `clickpad2__dialog--${buttonId}`)
    // dialog の中身を生成する
    // form で囲む
    const form = document.createElement('form')

    const content = document.createElement('div')
    content.classList.add('clickpad2__dialog-content')
    buttonDefinition.dialog.forEach(({ message, tip, option }, index) => {
      const wrapper = document.createElement('div')
      wrapper.classList.add('clickpad2__dialog-item', `clickpad2__dialog-item--${option.type}`)

      const id = `dialog-control-${index + 1}`

      switch (option.type) {
        case 'text': {
          const item = document.createElement('div')
          item.classList.add('clickpad2__dialog-text-item')

          if (message !== undefined) {
            const label = document.createElement('label')
            label.classList.add('clickpad2__dialog-item-label')
            label.htmlFor = id

            const labelTitle = document.createElement('span')
            labelTitle.classList.add('clickpad2__dialog-item-label-title')
            labelTitle.textContent = message
            label.appendChild(labelTitle)

            if (tip !== undefined) {
              const small = document.createElement('small')
              small.textContent = tip
              label.appendChild(small)
            }
            item.appendChild(label)
          }

          const input = document.createElement('input')
          input.id = id
          input.name = id
          input.onkeydown = (e) => {
            e.stopPropagation()
          }
          input.type = 'text'
          if (option.useSelection) {
            const textarea = document.querySelector('#msg')
            const selectedText = textarea.value.substring(textarea.selectionStart, textarea.selectionEnd)
            input.value = selectedText
          }
          if (option.prefix !== undefined) {
            input.dataset.prefix = option.prefix
          }
          item.appendChild(input)
          wrapper.appendChild(item)
          content.appendChild(wrapper)
          break
        }
        case 'checkbox': {
          const item = document.createElement('div')
          item.classList.add('clickpad2__dialog-checkbox-item')

          if (message !== undefined) {
            const label = document.createElement('label')
            label.classList.add('clickpad2__dialog-item-label')

            const labelTitle = document.createElement('span')
            labelTitle.classList.add('clickpad2__dialog-item-label-title')
            labelTitle.textContent = message
            label.appendChild(labelTitle)
            
            if (tip !== undefined) {
              const small = document.createElement('small')
              small.textContent = tip
              label.appendChild(small)
            }
            item.appendChild(label)
          }

          const choices = document.createElement('div')
          choices.classList.add('clickpad2__dialog-choices')
          // option.values ごとに label>input を生成する
          option.values.forEach(({ label, value }, index2) => {
            const _id = `${id}-${index2 + 1}`
            const labelElement = document.createElement('label')
            labelElement.classList.add('clickpad2__dialog-checkbox-item-label')
            const input = document.createElement('input')
            input.id = _id
            input.type = 'checkbox'
            input.name = id
            input.value = value
            labelElement.appendChild(input)
            const labelText = document.createElement('span')  
            labelText.textContent = label
            labelElement.appendChild(labelText)

            choices.appendChild(labelElement)
          })
          item.appendChild(choices)
          wrapper.appendChild(item)
          content.appendChild(wrapper)
          break
        }
        case 'radio': {
          const item = document.createElement('div')
          item.classList.add('clickpad2__dialog-radio-item')

          if (message !== undefined) {
            const label = document.createElement('label')
            label.classList.add('clickpad2__dialog-item-label')

            const labelTitle = document.createElement('span')
            labelTitle.classList.add('clickpad2__dialog-item-label-title')
            labelTitle.textContent = message
            label.appendChild(labelTitle)

            if (tip !== undefined) {
              const small = document.createElement('small')
              small.textContent = tip
              label.appendChild(small)
            }
            item.appendChild(label)
          }

          const choices = document.createElement('div')
          choices.classList.add('clickpad2__dialog-choices')          // option.values ごとに label>input を生成する
          option.values.forEach(({ label, color, icon, value, checked }, index2) => {
            const _id = `${id}-${index2 + 1}`
            const labelElement = document.createElement('label')
            labelElement.classList.add('clickpad2__dialog-radio-item-label')
            const input = document.createElement('input')
            input.id = _id
            input.type = 'radio'
            input.name = id
            input.value = value
            input.checked = checked
            labelElement.appendChild(input)
            if (color !== undefined) {
              labelElement.classList.add('clickpad2__dialog-radio-item-label--color')
              // 色名をlabel, color を背景色にする
              const colorBox = document.createElement('span')
              colorBox.classList.add('clickpad2__dialog-radio-color-box')
              colorBox.title = label
              colorBox.style.backgroundColor = color
              labelElement.appendChild(colorBox)
            } else if (icon !== undefined) {
              labelElement.classList.add('clickpad2__dialog-radio-item-label--icon')
              // icon をラベルの前に配置する
              const iconElement = document.createElement('span')
              iconElement.classList.add('material-icons-outlined')
              iconElement.textContent = icon
              labelElement.appendChild(iconElement)
              const text = document.createElement('span')
              text.textContent = label
              text.classList.add('clickpad2__dialog-radio-item-label-text')
              labelElement.appendChild(text)
            } else {
              labelElement.appendChild(document.createTextNode(label))
            }
            choices.appendChild(labelElement)
          })
          item.appendChild(choices)
          wrapper.appendChild(item)
          content.appendChild(wrapper)
          break
        }
        case 'select': {
          const item = document.createElement('div')
          item.classList.add('clickpad2__dialog-select-item')

          if (message !== undefined) {
            const label = document.createElement('label')
            label.classList.add('clickpad2__dialog-item-label')

            const labelTitle = document.createElement('span')
            labelTitle.classList.add('clickpad2__dialog-item-label-title')
            labelTitle.textContent = message
            label.appendChild(labelTitle)

            if (tip !== undefined) {
              const small = document.createElement('small')
              small.textContent = tip
              label.appendChild(small)
            }
            item.appendChild(label)
          }

          const choices = document.createElement('div')
          choices.classList.add('clickpad2__dialog-choices')
          // option.values ごとに label>input を生成する
          option.values.forEach(({ label, value, checked }, index2) => {
            const _id = `${id}-${index2 + 1}`
            const labelElement = document.createElement('label')
            labelElement.classList.add('clickpad2__dialog-select-item-label')
            const input = document.createElement('input')
            input.id = _id
            input.type = 'checkbox'
            input.name = id
            input.value = value
            input.checked = checked
            labelElement.appendChild(input)
            labelElement.appendChild(document.createTextNode(label))
            choices.appendChild(labelElement)
          })
          item.appendChild(choices)
          wrapper.appendChild(item)
          content.appendChild(wrapper)
          break
        }
        case 'font-size-guide': {
          const item = document.createElement('div')
          item.classList.add('clickpad2__dialog-font-size-guide-item')

          // 凡例
          const legend = document.createElement('div')
          legend.classList.add('clickpad2__dialog-font-size-guide-item-legend')
          const legendTitle = document.createElement('h3')
          legendTitle.textContent = '[ 文字サイズ指定キーワード ]'
          legend.appendChild(legendTitle)

          const legendTip1 = document.createElement('p')
          legendTip1.textContent = 'xx-small / x-small / small / medium（初期値）'
          const legendTip2 = document.createElement('p')
          legendTip2.textContent = 'large / x-large / xx-large'
          legend.appendChild(legendTip1)
          legend.appendChild(legendTip2)
          item.appendChild(legend)

          wrapper.appendChild(item)
          content.appendChild(wrapper)
          break
        }
        case 'section-header': {
          const item = document.createElement('div')
          item.classList.add('clickpad2__dialog-section-header-item')

          // タイトル
          const heading = document.createElement('h2')
          heading.textContent = message
          item.appendChild(heading)

          wrapper.appendChild(item)
          content.appendChild(wrapper)
          break
        }
        case 'icon-header': {
          const item = document.createElement('div')
          item.classList.add('clickpad2__dialog-icon-header-item')

          // タイトル
          const title = document.createElement('h3')
          title.textContent = 'Google アイコン検索リンク'
          title.classList.add('clickpad2__dialog-icon-header-item-title')
          item.appendChild(title)

          const linkGrid = document.createElement('div')
          linkGrid.classList.add('clickpad2__dialog-icon-header-item-link-grid')

          // Material Icons へのリンク
          const materialIconsLink = document.createElement('a')
          materialIconsLink.textContent = 'Material Icons'
          materialIconsLink.href = 'https://fonts.google.com/icons?icon.set=Material+Icons'
          materialIconsLink.target = '_blank'
          linkGrid.appendChild(materialIconsLink)

          // Material Symbold へのリンク
          const materialSymbolsLink = document.createElement('a')
          materialSymbolsLink.textContent = 'Material Symbols'
          materialSymbolsLink.href = 'https://fonts.google.com/icons?icon.set=Material+Symbols'
          materialSymbolsLink.target = '_blank'
          linkGrid.appendChild(materialSymbolsLink)

          item.appendChild(linkGrid)

          // 説明文
          const description = document.createElement('p')
          description.textContent = '(アイコンをクリックして表示される\n右側ウィンドウ内の<span ...から始まる枠内のコードを [アイコンコード] に入力します）'
          item.appendChild(description)

          wrapper.appendChild(item)
          content.appendChild(wrapper)
          break
        }
      }
    })
    form.appendChild(content)
    dialog.appendChild(form)

    const action = document.createElement('div')
    action.classList.add('clickpad2__dialog-action')

    // キャンセルボタンを生成する
    const close = document.createElement('button')
    close.classList.add('btn', 'btn-link', 'btn-sm')
    close.type = 'button'
    close.textContent = 'キャンセル'
    close.onclick = () => {
      dialog.close()
      document.querySelector('#msg').focus()
    }
    action.appendChild(close)

    // OKボタンを生成する
    const insert = document.createElement('button')
    insert.classList.add('btn', 'btn-primary', 'btn-sm', 'clickpad2__dialog-action-insert')
    insert.type = 'submit'
    insert.textContent = 'OK'
    insert.onclick = (e) => {
      e.preventDefault()
      const textarea = document.querySelector('#msg')
      const { selectionStart, selectionEnd } = textarea
      const selectedText = textarea.value.substring(selectionStart, selectionEnd)
      const textBefore = textarea.value.substring(0, selectionStart)
      const textAfter = textarea.value.substring(selectionEnd)
      // テンプレート文字列を展開する
      let insertText = buttonDefinition.value
      // フォームから FormData を取得する
      const form = dialog.querySelector('form')
      const formData = new FormData(form)

      // formData の内容を使って置換する
      const formValues = Array.from(formData.entries()).reduce((memo, [key, value]) => {
        // key 毎にvalueを格納する
        if (memo[key] !== undefined) {
          memo[key].push(value)
        } else {
          memo[key] = [value]
        }
        return memo
      }, {})

      for (const [key, values] of Object.entries(formValues)) {
        const index = key.match(/(\d+)/)[1]
        console.log({ index, values })
        const prefix = form.querySelector(`[name="${key}"]`)?.dataset?.prefix ?? ''
        const joinedValue = values.join(',')
        const valueText = joinedValue.length > 0 ? prefix + joinedValue : joinedValue
        insertText = insertText.replace('${'+ index + '}', valueText)
      }
      // 残った ${\d+} を削除する
      insertText = insertText.replace(/\$\{\d+\}/g, '')

      insertText = insertText.replace('${selection}', selectedText)
      textarea.value = textBefore + insertText + textAfter
      textarea.setSelectionRange(selectionStart, selectionStart + insertText.length)
      dialog.close()
      textarea.focus()
    }
    action.prepend(insert)

    form.appendChild(action)

    // dialog を body に追加する
    document.body.appendChild(dialog)

    // dialog を表示する
    dialog.showModal()
    dialog.onclose = () => {
      dialog.remove()
    }
  }

  return button
}